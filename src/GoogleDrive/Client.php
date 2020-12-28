<?php

declare(strict_types=1);

namespace Keboola\GoogleDriveExtractor\GoogleDrive;

use GuzzleHttp\Psr7\Response;
use Keboola\Google\ClientBundle\Google\RestApi as GoogleApi;

class Client
{
    protected const DRIVE_FILES = 'https://www.googleapis.com/drive/v3/files';

    protected const DRIVE_UPLOAD = 'https://www.googleapis.com/upload/drive/v3/files';

    protected const SPREADSHEETS = 'https://sheets.googleapis.com/v4/spreadsheets/';

    protected GoogleApi $api;

    public function __construct(GoogleApi $api)
    {
        $this->api = $api;
    }

    public function getApi(): GoogleApi
    {
        return $this->api;
    }

    public function getFile(string $id): array
    {
        $response = $this->api->request(
            self::DRIVE_FILES . '/' . $id,
            'GET'
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    public function createFile(string $pathname, string $title): array
    {
        $body = [
            'name' => $title,
            'mimeType' => 'application/vnd.google-apps.spreadsheet',
        ];

        $response = $this->api->request(
            self::DRIVE_FILES,
            'POST',
            [
                'Content-Type' => 'application/json',
            ],
            [
                'json' => $body,
            ]
        );

        $responseJson = json_decode((string) $response->getBody()->getContents(), true);

        $mediaUrl = sprintf('%s/%s?uploadType=media', self::DRIVE_UPLOAD, $responseJson['id']);

        $response = $this->api->request(
            $mediaUrl,
            'PATCH',
            [
                'Content-Type' => 'text/csv',
                'Content-Length' => filesize($pathname),
            ],
            [
                'body' => \GuzzleHttp\Psr7\stream_for(fopen($pathname, 'r')),
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    public function deleteFile(string $id): Response
    {
        return $this->api->request(
            sprintf('%s/%s', self::DRIVE_FILES, $id),
            'DELETE'
        );
    }

    public function getSpreadsheet(string $fileId): array
    {
        $fields = [
            'spreadsheetId',
            'properties.title',
            'sheets.properties.gridProperties',
            'sheets.properties.sheetId',
            'sheets.properties.title',
        ];
        $response = $this->api->request(
            $this->addFields(sprintf('%s%s', self::SPREADSHEETS, $fileId), $fields),
            'GET',
            [
                'Accept' => 'application/json',
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    public function getSpreadsheetValues(string $spreadsheetId, string $range): array
    {
        $response = $this->api->request(
            sprintf(
                '%s%s/values/%s',
                self::SPREADSHEETS,
                $spreadsheetId,
                $range
            ),
            'GET',
            [
                'Accept' => 'application/json',
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    protected function addFields(string $uri, array $fields = []): string
    {
        if (empty($fields)) {
            return $uri;
        }
        $delimiter = (strstr($uri, '?') === false) ? '?' : '&';
        return $uri . sprintf('%sfields=%s', $delimiter, implode(',', $fields));
    }
}
