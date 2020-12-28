<?php

declare(strict_types=1);

namespace Keboola\GoogleDriveExtractor\Extractor;

use Keboola\GoogleDriveExtractor\Exception\UserException;
use Keboola\GoogleDriveExtractor\GoogleDrive\Client;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;

class Extractor
{
    private Client $driveApi;

    private Output $output;

    private Logger $logger;

    public function __construct(Client $driveApi, Output $output, Logger $logger)
    {
        $this->driveApi = $driveApi;
        $this->logger = $logger;
        $this->output = $output;

        $this->driveApi->getApi()->setBackoffsCount(9);
        $this->driveApi->getApi()->setBackoffCallback403($this->getBackoffCallback403());
        $this->driveApi->getApi()->setRefreshTokenCallback([$this, 'refreshTokenCallback']);
    }

    public function getBackoffCallback403(): callable
    {
        return function ($response) {
            /** @var ResponseInterface $response */
            $reason = $response->getReasonPhrase();

            if ($reason === 'insufficientPermissions'
                || $reason === 'dailyLimitExceeded'
                || $reason === 'usageLimits.userRateLimitExceededUnreg'
            ) {
                return false;
            }

            return true;
        };
    }

    public function run(array $sheets): array
    {
        $status = [];
        $exceptionHandler = new ExceptionHandler();

        foreach ($sheets as $sheet) {
            if (!$sheet['enabled']) {
                continue;
            }

            try {
                $spreadsheet = $this->driveApi->getSpreadsheet($sheet['fileId']);
                $this->logger->info('Obtained spreadsheet metadata');

                try {
                    $this->logger->info('Extracting sheet ' . $sheet['sheetTitle']);
                    $this->export($spreadsheet, $sheet);
                } catch (UserException $e) {
                    throw new UserException($e->getMessage(), 0, $e);
                } catch (\Throwable $e) {
                    $exceptionHandler->handleExportException($e, $sheet);
                }
            } catch (UserException $e) {
                throw new UserException($e->getMessage(), 0, $e);
            } catch (\Throwable $e) {
                $exceptionHandler->handleGetSpreadsheetException($e, $sheet);
            }

            $status[$sheet['fileTitle']][$sheet['sheetTitle']] = 'success';
        }

        return $status;
    }

    private function export(array $spreadsheet, array $sheetCfg): void
    {
        $sheet = $this->getSheetById($spreadsheet['sheets'], (string) $sheetCfg['sheetId']);
        $rowCount = $sheet['properties']['gridProperties']['rowCount'];
        $columnCount = $sheet['properties']['gridProperties']['columnCount'];
        $offset = 1;
        $limit = 1000;

        while ($offset <= $rowCount) {
            $this->logger->info(sprintf('Extracting rows %s to %s', $offset, $offset+$limit));
            $range = $this->getRange($sheet['properties']['title'], $columnCount, $offset, $limit);

            $response = $this->driveApi->getSpreadsheetValues(
                $spreadsheet['spreadsheetId'],
                $range
            );

            if (!empty($response['values'])) {
                if ($offset === 1) {
                    // it is a first run
                    $csvFilename = $this->output->createCsv($sheetCfg);
                    $this->output->createManifest($csvFilename, $sheetCfg['outputTable']);
                }

                $this->output->write($response['values'], $offset);
            }

            $offset += $limit;
        }
    }

    private function getSheetById(array $sheets, string $id): array
    {
        foreach ($sheets as $sheet) {
            if ((string) $sheet['properties']['sheetId'] === $id) {
                return $sheet;
            }
        }

        throw new UserException(sprintf('Sheet id "%s" not found', $id));
    }

    public function getRange(string $sheetTitle, int $columnCount, int $rowOffset = 1, int $rowLimit = 1000): string
    {
        $lastColumn = $this->columnToLetter($columnCount);

        $start = 'A' . $rowOffset;
        $end = $lastColumn . ($rowOffset + $rowLimit - 1);

        return urlencode($sheetTitle) . '!' . $start . ':' . $end;
    }

    public function columnToLetter(int $column): string
    {
        $alphas = range('A', 'Z');
        $letter = '';

        while ($column > 0) {
            $remainder = ($column - 1) % 26;
            $letter = $alphas[$remainder] . $letter;
            $column = ($column - $remainder - 1) / 26;
        }

        return $letter;
    }

    public function refreshTokenCallback(string $accessToken, string $refreshToken): void
    {
    }
}
