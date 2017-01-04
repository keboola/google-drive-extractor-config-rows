<?php
/**
 * RestApi.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 26.7.13
 */

namespace Keboola\GoogleDriveExtractor\GoogleDrive;

use Keboola\Google\ClientBundle\Google\RestApi as GoogleApi;
use Keboola\GoogleDriveExtractor\Exception\ApplicationException;

class Client
{
    /** @var GoogleApi */
    protected $api;

    const DRIVE_FILES = 'https://www.googleapis.com/drive/v3/files';

    const DRIVE_UPLOAD = 'https://www.googleapis.com/upload/drive/v3/files';

    const SPREADSHEETS = 'https://sheets.googleapis.com/v4/spreadsheets/';

    public function __construct(GoogleApi $api)
    {
        $this->api = $api;
    }

    /**
     * @return GoogleApi
     */
    public function getApi()
    {
        return $this->api;
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Keboola\Google\ClientBundle\Exception\RestApiException
     */
    public function getFile($id)
    {
        $response = $this->api->request(
            self::DRIVE_FILES . '/' . $id,
            'GET'
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param $pathname
     * @param $title
     * @return array
     * @throws \Keboola\Google\ClientBundle\Exception\RestApiException
     */
    public function createFile($pathname, $title)
    {
        $body = [
            'name' => $title,
            'mimeType' => 'application/vnd.google-apps.spreadsheet'
        ];

        $response = $this->api->request(
            self::DRIVE_FILES,
            'POST',
            [
                'Content-Type' => 'application/json',
            ],
            [
                'json' => $body
            ]
        );

        $responseJson = json_decode($response->getBody(), true);

        $mediaUrl = sprintf('%s/%s?uploadType=media', self::DRIVE_UPLOAD, $responseJson['id']);

        $response = $this->api->request(
            $mediaUrl,
            'PATCH',
            [
                'Content-Type' => 'text/csv',
                'Content-Length' => filesize($pathname)
            ],
            [
                'body' => \GuzzleHttp\Psr7\stream_for(fopen($pathname, 'r'))
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param $id
     * @return \GuzzleHttp\Psr7\Response
     * @throws \Keboola\Google\ClientBundle\Exception\RestApiException
     */
    public function deleteFile($id)
    {
        return $this->api->request(
            sprintf('%s/%s', self::DRIVE_FILES, $id),
            'DELETE'
        );
    }

    /**
     * Returns list of sheet for given document
     *
     * @param $fileId
     * @return array|bool
     * @throws ApplicationException
     * @throws \Keboola\Google\ClientBundle\Exception\RestApiException
     */
    public function getSpreadsheet($fileId)
    {
        $response = $this->api->request(
            sprintf('%s%s', self::SPREADSHEETS, $fileId),
            'GET',
            [
                'Accept' => 'application/json',
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param $spreadsheetId
     * @param $range
     * @return array
     * @throws \Keboola\Google\ClientBundle\Exception\RestApiException
     */
    public function getSpreadsheetValues($spreadsheetId, $range)
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
}
