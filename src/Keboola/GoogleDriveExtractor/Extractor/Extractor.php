<?php
/**
 * Extractor.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 29.7.13
 */

namespace Keboola\GoogleDriveExtractor\Extractor;

use GuzzleHttp\Exception\RequestException;
use Keboola\GoogleDriveExtractor\Exception\ApplicationException;
use Keboola\GoogleDriveExtractor\Exception\UserException;
use Keboola\GoogleDriveExtractor\GoogleDrive\Client;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;

class Extractor
{
    /** @var Client */
    private $driveApi;

    /** @var Output */
    private $output;

    /** @var Logger */
    private $logger;

    public function __construct(Client $driveApi, Output $output, Logger $logger)
    {
        $this->driveApi = $driveApi;
        $this->logger = $logger;
        $this->output = $output;

        $this->driveApi->getApi()->setBackoffsCount(7);
        $this->driveApi->getApi()->setBackoffCallback403($this->getBackoffCallback403());
        $this->driveApi->getApi()->setRefreshTokenCallback([$this, 'refreshTokenCallback']);
    }

    public function getBackoffCallback403()
    {
        return function ($response) {
            /** @var ResponseInterface $response */
            $reason = $response->getReasonPhrase();

            if ($reason == 'insufficientPermissions'
                || $reason == 'dailyLimitExceeded'
                || $reason == 'usageLimits.userRateLimitExceededUnreg'
            ) {
                return false;
            }

            return true;
        };
    }

    public function run(array $sheets)
    {
        $status = [];

        foreach ($sheets as $sheet) {
            $this->logger->info('Importing sheet ' . $sheet['sheetTitle']);

            try {
                $meta = $this->driveApi->getFile($sheet['fileId']);
            } catch (RequestException $e) {
                if ($e->getResponse()->getStatusCode() == 404) {
                    throw new UserException(sprintf("File '%s' not found in Google Drive", $sheet['sheetName']), $e);
                } else {
                    $userException = new UserException("Google Drive Error: " . $e->getMessage(), $e);
                    $userException->setData(array(
                        'message' => $e->getMessage(),
                        'reason'  => $e->getResponse()->getReasonPhrase(),
                        'sheet'   => $sheet
                    ));
                    throw $userException;
                }
            }

            if (!isset($meta['exportLinks'])) {
                $e = new ApplicationException("ExportLinks missing in file resource");
                $e->setData([
                    'fileMetadata' => $meta
                ]);
                throw $e;
            }

            if (isset($meta['exportLinks']['text/csv'])) {
                $exportLink = $meta['exportLinks']['text/csv'] . '&gid=' . $sheet['sheetId'];
            } else {
                $exportLink = str_replace('pdf', 'csv', $meta['exportLinks']['application/pdf']) . '&gid=' . $sheet['sheetId'];
            }

            try {
                $stream = $this->driveApi->export($exportLink);

                if ($stream->getSize() > 0) {
                    $this->output->save($stream, $sheet);
                } else {
                    $this->logger->warning(sprintf(
                        "Sheet is empty. File: '%s', Sheet: '%s'.",
                        $sheet['fileTitle'],
                        $sheet['sheetTitle']
                    ));
                    $status[$sheet['sheetTitle']] = "file is empty";
                }
            } catch (RequestException $e) {
                $userException = new UserException("Error importing file - sheet: '" . $sheet['fileTitle'] . " - " . $sheet['sheetTitle'] . "'. ", $e);
                $userException->setData(array(
                    'message' => $e->getMessage(),
                    'reason'  => $e->getResponse()->getReasonPhrase(),
                    'body'    => substr($e->getResponse()->getBody(), 0, 300),
                    'sheet'   => $sheet
                ));
                throw $userException;
            }
        }

        return $status;
    }

    public function refreshTokenCallback($accessToken, $refreshToken)
    {
    }
}
