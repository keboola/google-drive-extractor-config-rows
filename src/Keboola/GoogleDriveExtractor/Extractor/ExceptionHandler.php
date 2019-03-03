<?php

namespace Keboola\GoogleDriveExtractor\Extractor;

use GuzzleHttp\Exception\RequestException;
use Keboola\GoogleDriveExtractor\Exception\ApplicationException;
use Keboola\GoogleDriveExtractor\Exception\UserException;

class ExceptionHandler
{
    public function handleGetSpreadsheetException(\Exception $e, array $sheet)
    {
        if (($e instanceof RequestException) && ($e->getResponse() !== null)) {
            if ($e->getResponse()->getStatusCode() === 404) {
                throw new UserException(sprintf('File "%s" not found in Google Drive', $sheet['sheetTitle']), 404, $e);
            }

            $errorSpec = json_decode($e->getResponse()->getBody(), true);

            if (array_key_exists('error', $errorSpec)) {
                if ($errorSpec['error'] === 'invalid_grant') {
                    throw new UserException(sprintf(
                        'Invalid OAuth grant when fetching "%s", try reauthenticating the extractor',
                        $sheet['fileTitle']
                    ), 0, $e);
                }
                throw new UserException(sprintf(
                    '%s (%s)',
                    $errorSpec['error_description'],
                    $errorSpec['error']
                ), 0, $e);
            }

            $userException = new UserException("Google Drive Error: " . $e->getMessage(), 400, $e);
            $userException->setData([
                'message' => $e->getMessage(),
                'reason' => $e->getResponse()->getReasonPhrase(),
                'sheet' => $sheet,
            ]);
            throw $userException;
        }
        throw new ApplicationException(
            $e->getMessage(),
            $e->getCode(),
            $e
        );
    }

    public function handleExportException(\Exception $e, $sheet)
    {
        if (($e instanceof RequestException) && ($e->getResponse() !== null)) {
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
        throw new ApplicationException(
            $e->getMessage(),
            $e->getCode(),
            $e
        );
    }
}
