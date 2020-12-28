<?php

declare(strict_types=1);

namespace Keboola\GoogleDriveExtractor\Tests\GoogleDrive;

use Keboola\Google\ClientBundle\Google\RestApi;
use Keboola\GoogleDriveExtractor\GoogleDrive\Client;
use Keboola\GoogleDriveExtractor\Tests\BaseTest;

class ClientTest extends BaseTest
{
    private Client $client;

    public function setUp(): void
    {
        parent::setUp();
        $api = new RestApi((string) getenv('CLIENT_ID'), (string) getenv('CLIENT_SECRET'));
        $api->setCredentials((string) getenv('ACCESS_TOKEN'), (string) getenv('REFRESH_TOKEN'));
        $this->client = new Client($api);
    }

    public function testGetFile(): void
    {
        $file = $this->client->getFile($this->testFile['spreadsheetId']);

        $this->assertArrayHasKey('id', $file);
        $this->assertArrayHasKey('name', $file);
        $this->assertEquals($this->testFile['spreadsheetId'], $file['id']);
    }

    public function testGetSpreadsheet(): void
    {
        $spreadsheet = $this->client->getSpreadsheet($this->testFile['spreadsheetId']);

        $this->assertArrayHasKey('spreadsheetId', $spreadsheet);
        $this->assertArrayHasKey('properties', $spreadsheet);
        $this->assertArrayHasKey('sheets', $spreadsheet);
    }

    public function testGetSpreadsheetValues(): void
    {
        $spreadsheetId = $this->testFile['spreadsheetId'];
        $sheetTitle = $this->testFile['sheets'][0]['properties']['title'];

        $response = $this->client->getSpreadsheetValues($spreadsheetId, $sheetTitle);

        $this->assertArrayHasKey('range', $response);
        $this->assertArrayHasKey('majorDimension', $response);
        $this->assertArrayHasKey('values', $response);
        $header = $response['values'][0];
        $this->assertEquals('Class', $header[1]);
        $this->assertEquals('Sex', $header[2]);
        $this->assertEquals('Age', $header[3]);
        $this->assertEquals('Survived', $header[4]);
        $this->assertEquals('Freq', $header[5]);
    }
}
