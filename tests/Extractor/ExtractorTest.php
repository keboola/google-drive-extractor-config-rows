<?php

namespace Keboola\GoogleDriveExtractor\Tests\Extractor;

use Keboola\Google\ClientBundle\Google\RestApi;
use Keboola\GoogleDriveExtractor\Extractor\Extractor;
use Keboola\GoogleDriveExtractor\Extractor\Output;
use Keboola\GoogleDriveExtractor\GoogleDrive\Client;
use Keboola\GoogleDriveExtractor\Logger;
use PHPUnit\Framework\TestCase;

class ExtractorTest extends TestCase
{
    /** @var Client */
    private $googleDriveClient;

    /** @var Extractor */
    private $extractor;

    public function setUp()
    {
        $api = new RestApi(getenv('CLIENT_ID'), getenv('CLIENT_SECRET'));
        $api->setCredentials(getenv('ACCESS_TOKEN'), getenv('REFRESH_TOKEN'));
        $this->googleDriveClient = new Client($api);
        $output = new Output('/data', 'in.c-ex-google-drive');
        $logger = new Logger('tests');
        $this->extractor = new Extractor($this->googleDriveClient, $output, $logger);
    }

    public function testColumnToLetter()
    {
        $notation = $this->extractor->columnToLetter(76);
        $this->assertEquals('BX', $notation);

        $notation = $this->extractor->columnToLetter(1);
        $this->assertEquals('A', $notation);

        $notation = $this->extractor->columnToLetter(26);
        $this->assertEquals('Z', $notation);

        $notation = $this->extractor->columnToLetter(27);
        $this->assertEquals('AA', $notation);
    }
}
