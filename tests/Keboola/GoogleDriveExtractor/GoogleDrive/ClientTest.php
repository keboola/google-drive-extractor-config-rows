<?php
use Keboola\Google\ClientBundle\Google\RestApi;
use Keboola\GoogleDriveExtractor\GoogleDrive\Client;
use Keboola\GoogleDriveExtractor\Test\BaseTest;

/**
 * Created by PhpStorm.
 * User: miroslavcillik
 * Date: 11/08/16
 * Time: 14:33
 */
class ClientTest extends BaseTest
{
    /** @var Client */
    private $client;

    public function setUp()
    {
        $api = new RestApi(getenv('CLIENT_ID'), getenv('CLIENT_SECRET'));
        $api->setCredentials(getenv('ACCESS_TOKEN'), getenv('REFRESH_TOKEN'));
        $this->client = new Client($api);
    }

    public function testGetFile()
    {
        $fileId = '1tep21r8fDJyXJyMAo2KKqBrxaEmqoJuwnQB4Y6gqGBU';
        $file = $this->client->getFile($fileId);

        $this->assertArrayHasKey('id', $file);
        $this->assertArrayHasKey('title', $file);
        $this->assertArrayHasKey('exportLinks', $file);
        $this->assertEquals($fileId, $file['id']);
    }

    public function testExport()
    {
        $fileId = '1tep21r8fDJyXJyMAo2KKqBrxaEmqoJuwnQB4Y6gqGBU';
        $sheetId = 10;
        $meta = $this->client->getFile($fileId);

        if (isset($meta['exportLinks']['text/csv'])) {
            $exportLink = $meta['exportLinks']['text/csv'] . '&gid=' . $sheetId;
        } else {
            $exportLink = str_replace('pdf', 'csv', $meta['exportLinks']['application/pdf']) . '&gid=' . $sheetId;
        }

        $content = $this->client->export($exportLink);

        var_dump($content); die;
    }

}
