<?php

namespace Keboola\GoogleDriveExtractor\Extractor;

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

        $this->driveApi->getApi()->setBackoffsCount(9);
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

        $exceptionHandler = new ExceptionHandler();

        foreach ($sheets as $sheet) {
            if (!$sheet['enabled']) {
                continue;
            }

            $this->logger->info('Importing sheet ' . $sheet['sheetTitle']);

            try {
                $spreadsheet = $this->driveApi->getSpreadsheet($sheet['fileId']);
            } catch (\Exception $e) {
                $exceptionHandler->handleGetSpreadsheetException($e, $sheet);
            }

            try {
                $this->export($spreadsheet, $sheet);
            } catch (\Exception $e) {
                $exceptionHandler->handleExportException($e, $sheet);
            }

            $status[$sheet['fileTitle']][$sheet['sheetTitle']] = 'success';
        }

        return $status;
    }

    private function export($spreadsheet, $sheetCfg)
    {
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
                if ($offset == 1) {
                    // it is a first run
                    $csv = $this->output->createCsv($sheetCfg);
                    $this->output->createManifest($csv->getPathname(), $sheetCfg['outputTable']);
                }

                $this->output->write($response['values'], $offset);
            }

            $offset += $limit;
        }
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

    public function getRange($sheetTitle, $columnCount, $rowOffset = 1, $rowLimit = 1000)
    {
        $lastColumn = $this->columnToLetter($columnCount);

        $start = 'A' . $rowOffset;
        $end = $lastColumn . ($rowOffset + $rowLimit - 1);

        return urlencode($sheetTitle) . '!' . $start . ':' . $end;
    }

    public function columnToLetter($column)
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

    public function refreshTokenCallback($accessToken, $refreshToken)
    {
    }
}
