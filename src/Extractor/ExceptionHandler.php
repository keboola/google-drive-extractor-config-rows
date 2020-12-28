<?php

declare(strict_types=1);

namespace Keboola\GoogleDriveExtractor\Extractor;

use GuzzleHttp\Exception\RequestException;
use Keboola\Component\UserException;
use Keboola\GoogleDriveExtractor\Exception\ApplicationException;

class ExceptionHandler
{
    public function handleGetSpreadsheetException(\Throwable $e, array $sheet): void
    {
        if (($e instanceof RequestException) && ($e->getResponse() !== null)) {
            if ($e->getResponse()->getStatusCode() === 404) {
                throw new UserException(sprintf('File "%s" not found in Google Drive', $sheet['sheetTitle']), 404, $e);
            }

            $errorSpec = json_decode((string) $e->getResponse()->getBody()->getContents(), true);

            if (array_key_exists('error', $errorSpec)) {
                if ($errorSpec['error'] === 'invalid_grant') {
                    throw new UserException(
                        sprintf(
                            'Invalid OAuth grant when fetching "%s", try reauthenticating the extractor',
                            $sheet['fileTitle']
                        ),
                        0,
                        $e
                    );
                }

                if (is_array($errorSpec['error'])
                    && count($errorSpec['error']) > 1
                    && !isset($errorSpec['error_description'])
                ) {
                    throw new UserException(
                        sprintf(
                            '"%s" (%s) for "%s"',
                            $errorSpec['error']['message'],
                            $errorSpec['error']['status'],
                            $sheet['sheetTitle']
                        ),
                        0,
                        $e
                    );
                }

                if (isset($errorSpec['error_description'])) {
                    throw new UserException(
                        sprintf(
                            '"%s" (%s)',
                            $errorSpec['error_description'],
                            $errorSpec['error']
                        ),
                        0,
                        $e
                    );
                }
            }

            throw new UserException('Google Drive Error: ' . $e->getMessage(), 400, $e);
        }
        throw new ApplicationException(
            $e->getMessage(),
            $e->getCode(),
            $e
        );
    }

    public function handleExportException(\Throwable $e, array $sheet): void
    {
        if (($e instanceof RequestException) && ($e->getResponse() !== null)) {
            throw new UserException(
                sprintf(
                    "Error importing file - sheet: '%s - %s'",
                    $sheet['fileTitle'],
                    $sheet['sheetTitle']
                ),
                400,
                $e
            );
        }
        throw new ApplicationException(
            $e->getMessage(),
            $e->getCode(),
            $e
        );
    }
}
