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
        $fileId = getenv('FILE_ID');
        $file = $this->client->getFile($fileId);

        $this->assertArrayHasKey('id', $file);
        $this->assertArrayHasKey('title', $file);
        $this->assertArrayHasKey('exportLinks', $file);
        $this->assertEquals($fileId, $file['id']);
    }

    public function testExport()
    {
        $fileId = getenv('FILE_ID');
        $sheetId = getenv('SHEET_ID');
        $meta = $this->client->getFile($fileId);

        if (isset($meta['exportLinks']['text/csv'])) {
            $exportLink = $meta['exportLinks']['text/csv'] . '&gid=' . $sheetId;
        } else {
            $exportLink = str_replace('pdf', 'csv', $meta['exportLinks']['application/pdf']) . '&gid=' . $sheetId;
        }

        $content = $this->client->export($exportLink);

        $this->assertInstanceOf('GuzzleHttp\Psr7\Stream', $content);
        $this->assertGreaterThan(0, $content->getSize());
    }

}
