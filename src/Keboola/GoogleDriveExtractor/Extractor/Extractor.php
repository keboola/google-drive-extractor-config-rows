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
            if (!$sheet['enabled']) {
                continue;
            }

            $this->logger->info('Importing sheet ' . $sheet['sheetTitle']);

            try {
                $spreadsheet = $this->driveApi->getSpreadsheet($sheet['fileId']);
            } catch (RequestException $e) {
                if ($e->getResponse()->getStatusCode() == 404) {
                    throw new UserException(sprintf("File '%s' not found in Google Drive", $sheet['sheetName']), 404, $e);
                } else {
                    $userException = new UserException("Google Drive Error: " . $e->getMessage(), 400, $e);
                    $userException->setData(array(
                        'message' => $e->getMessage(),
                        'reason'  => $e->getResponse()->getReasonPhrase(),
                        'sheet'   => $sheet
                    ));
                    throw $userException;
                }
            }

            try {
                $this->export($spreadsheet, $sheet);
            } catch (RequestException $e) {
                $userException = new UserException(
                    sprintf(
                        "Error importing file - sheet: '%s - %s'",
                        $sheet['fileTitle'],
                        $sheet['sheetTitle']
                    ),
                    400,
                    $e
                );
                $userException->setData(array(
                    'message' => $e->getMessage(),
                    'reason'  => $e->getResponse()->getReasonPhrase(),
                    'body'    => substr($e->getResponse()->getBody()->getContents(), 0, 300),
                    'sheet'   => $sheet
                ));
                throw $userException;
            }

            $outputCsv = $this->output->process($sheet);
            $this->output->createManifest($outputCsv->getPathname(), $sheet['outputTable']);

            $status[$sheet['fileTitle']][$sheet['sheetTitle']] = 'success';
        }

        return $status;
    }

    private function export($spreadsheet, $sheetCfg)
    {
        $csv = $this->output->createCsv($sheetCfg);
        $sheet = $this->getSheetById($spreadsheet['sheets'], $sheetCfg['sheetId']);

        $rowCount = $sheet['properties']['gridProperties']['rowCount'];
        $columnCount = $sheet['properties']['gridProperties']['columnCount'];
        $offset = 1;
        $limit = 1000;

        while ($offset <= $rowCount) {
            $range = $this->getRange($sheet['properties']['title'], $columnCount, $offset, $limit);

            $response = $this->driveApi->getSpreadsheetValues(
                $spreadsheet['spreadsheetId'],
                $range
            );

            if (!empty($response['values'])) {
                $this->output->write($response['values']);
            }
            
            $offset += $limit;
        }

        return $csv;
    }

    /**
     * @param $sheets
     * @param $id
     * @return array|bool
     */
    private function getSheetById($sheets, $id)
    {
        foreach ($sheets as $sheet) {
            if ($sheet['properties']['sheetId'] == $id) {
                return $sheet;
            }
        }

        return false;
    }

    private function getRange($sheetTitle, $columnCount, $rowOffset = 1, $rowLimit = 1000)
    {
        $lastColumn = $this->getColumnA1($columnCount - 1);

        $start = 'A' . $rowOffset;
        $end = $lastColumn . ($rowOffset + $rowLimit - 1);

        return $sheetTitle . '!' . $start . ':' . $end;
    }

    private function getColumnA1($columnNumber)
    {
        $alphas = range('A', 'Z');

        $prefix = '';
        if ($columnNumber > 26) {
            $quotient = intval(floor($columnNumber/26));
            $prefix = $alphas[$quotient];
        }

        $remainder = $columnNumber%26;

        return $prefix . $alphas[$remainder];
    }

    public function refreshTokenCallback($accessToken, $refreshToken)
    {
    }
}
