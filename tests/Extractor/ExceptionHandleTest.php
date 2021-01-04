<?php

declare(strict_types=1);

namespace Keboola\GoogleDriveExtractor\Tests\Extractor;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Keboola\GoogleDriveExtractor\Exception\ApplicationException;
use Keboola\GoogleDriveExtractor\Exception\UserException;
use Keboola\GoogleDriveExtractor\Extractor\ExceptionHandler;
use PHPUnit\Framework\TestCase;

class ExceptionHandleTest extends TestCase
{
    /**
     * @psalm-param class-string<\Throwable> $expectedExceptionClass
     * @dataProvider provideExceptionsForGetSpreadsheet
     */
    public function testHandlingOfExceptions(
        string $expectedExceptionClass,
        string $expectedExceptionMessage,
        \Throwable $caughtException,
        array $sheet
    ): void {
        $handler = new ExceptionHandler();
        $this->expectException($expectedExceptionClass);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $handler->handleGetSpreadsheetException($caughtException, $sheet);
    }

    public function provideExceptionsForGetSpreadsheet(): array
    {
        return [
            'invalid grant' => [
                UserException::class,
                'Invalid OAuth grant when fetching "File title", try reauthenticating the extractor',
                new RequestException(
                    // phpcs:disable Generic.Files.LineLength
                    'Client error: `POST https://www.googleapis.com/oauth2/v3/token` resulted in a `400 Bad Request` response: { "error": "invalid_grant", "error_description": "Bad Request" }',
                    new Request('whatever', 'git'),
                    new Response(400, [], '{ "error": "invalid_grant", "error_description": "Bad Request" }')
                ),
                [
                    'id' => 2,
                    'fileId' => '1y_XXXXXXXXXXX',
                    'fileTitle' => 'File title',
                    'sheetId' => 'SheetIdXxxxx',
                    'sheetTitle' => 'Title XXXXXX',
                    'outputTable' => 'table',
                    'header' => ['rows' => 1, 'columns' => []],
                    'enabled' => true,
                ],
            ],
            'http404' => [
                UserException::class,
                'File "Title XXXXXX" not found in Google Drive',
                new RequestException(
                    // phpcs:disable Generic.Files.LineLength
                    'Client error: `POST https://www.googleapis.com/oauth2/v3/token` resulted in a `400 Bad Request` response: { "error": "invalid_grant", "error_description": "Bad Request" }',
                    new Request('whatever', 'git'),
                    new Response(404, [], '{}')
                ),
                [
                    'id' => 2,
                    'fileId' => '1y_XXXXXXXXXXX',
                    'fileTitle' => 'FileIdXxxxxx',
                    'sheetId' => 'SheetIdXxxxx',
                    'sheetTitle' => 'Title XXXXXX',
                    'outputTable' => 'table',
                    'header' => ['rows' => 1, 'columns' => []],
                    'enabled' => true,
                ],
            ],
            'other request exception without description' => [
                UserException::class,
                // phpcs:disable Generic.Files.LineLength
                'Google Drive Error: Client error: `POST https://www.googleapis.com/oauth2/v3/token` resulted in a `400 Bad Request` response: { "error": "invalid_grant", "error_description": "Bad Request" }',
                new RequestException(
                    'Client error: `POST https://www.googleapis.com/oauth2/v3/token` resulted in a `400 Bad Request` response: { "error": "invalid_grant", "error_description": "Bad Request" }',
                    new Request('whatever', 'git'),
                    new Response(403, [], '{}')
                ),
                [
                    'id' => 2,
                    'fileId' => '1y_XXXXXXXXXXX',
                    'fileTitle' => 'FileIdXxxxxx',
                    'sheetId' => 'SheetIdXxxxx',
                    'sheetTitle' => 'Title XXXXXX',
                    'outputTable' => 'table',
                    'header' => ['rows' => 1, 'columns' => []],
                    'enabled' => true,
                ],
            ],
            'other request exception with description' => [
                UserException::class,
                '"The column AX is not in the sheet" (out_of_range)',
                new RequestException(
                    // phpcs:disable Generic.Files.LineLength
                    'Client error: `POST https://www.googleapis.com/oauth2/v3/token` resulted in a `400 Bad Request` response: { "error": "out_of_range", "error_description": "The column AX is not in the sheet" }',
                    new Request('whatever', 'git'),
                    // phpcs:disable Generic.Files.LineLength
                    new Response(403, [], '{ "error": "out_of_range", "error_description": "The column AX is not in the sheet" }')
                ),
                [
                    'id' => 2,
                    'fileId' => '1y_XXXXXXXXXXX',
                    'fileTitle' => 'FileIdXxxxxx',
                    'sheetId' => 'SheetIdXxxxx',
                    'sheetTitle' => 'Title XXXXXX',
                    'outputTable' => 'table',
                    'header' => ['rows' => 1, 'columns' => []],
                    'enabled' => true,
                ],
            ],
            'other random exception' => [
                ApplicationException::class,
                'Timeout',
                new \Exception(
                    'Timeout'
                ),
                [
                    'id' => 2,
                    'fileId' => '1y_XXXXXXXXXXX',
                    'fileTitle' => 'FileIdXxxxxx',
                    'sheetId' => 'SheetIdXxxxx',
                    'sheetTitle' => 'Title XXXXXX',
                    'outputTable' => 'table',
                    'header' => ['rows' => 1, 'columns' => []],
                    'enabled' => true,
                ],
            ],
            'exception with another response array ' => [
                UserException::class,
                '"The caller does not have permission" (PERMISSION_DENIED) for "Title XXXXXX"',

                new RequestException(
                    // phpcs:disable Generic.Files.LineLength
                    'Client error: `POST https://sheets.googleapis.com/v4/spreadsheets/123` resulted in a `403 Forbidden` response: {"error": {"code": 403,"message": "The caller does not have permission","status": "PERMISSION_DENIED"}}',
                    new Request('whatever', 'git'),
                    // phpcs:disable Generic.Files.LineLength
                    new Response(400, [], '{ "error": { "code": 403, "message": "The caller does not have permission", "status": "PERMISSION_DENIED" } }')
                ),
                [
                    'id' => 2,
                    'fileId' => '1y_XXXXXXXXXXX',
                    'fileTitle' => 'File title',
                    'sheetId' => 'SheetIdXxxxx',
                    'sheetTitle' => 'Title XXXXXX',
                    'outputTable' => 'table',
                    'header' => ['rows' => 1, 'columns' => []],
                    'enabled' => true,
                ],
            ],
        ];
    }

    /**
     * @psalm-param class-string<\Throwable> $expectedExceptionClass
     * @dataProvider provideExceptionsForExport
     */
    public function testExportExceptionHandling(
        string $expectedExceptionClass,
        string $expectedExceptionMessage,
        \Throwable $caughtException,
        array $sheet
    ): void {
        $handler = new ExceptionHandler();
        $this->expectException($expectedExceptionClass);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $handler->handleExportException($caughtException, $sheet);
    }

    public function provideExceptionsForExport(): array
    {
        return [
            'Rate limit exceeded' => [
                UserException::class,
                "Error importing file - sheet: 'FileIdXxxxxx - Title XXXXXX'",
                new RequestException(
                    'Some eeror message... Resulted in HTTP 429',
                    new Request('whatever', 'git'),
                    // phpcs:disable Generic.Files.LineLength
                    new Response(429, [], '{"error": {"errors": [{"domain": "usageLimits","reason": "rateLimitExceeded","message": "Rate Limit Exceeded"}],"code": 429,"message": "Rate Limit Exceeded"}}')
                ),
                [
                    'id' => 2,
                    'fileId' => '1y_XXXXXXXXXXX',
                    'fileTitle' => 'FileIdXxxxxx',
                    'sheetId' => 'SheetIdXxxxx',
                    'sheetTitle' => 'Title XXXXXX',
                    'outputTable' => 'table',
                    'header' => ['rows' => 1, 'columns' => []],
                    'enabled' => true,
                ],
            ],
        ];
    }
}
