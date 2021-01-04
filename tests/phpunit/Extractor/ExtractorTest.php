<?php

declare(strict_types=1);

namespace Keboola\GoogleDriveExtractor\Tests\Extractor;

use Keboola\Component\Logger;
use Keboola\Google\ClientBundle\Google\RestApi;
use Keboola\GoogleDriveExtractor\Configuration\Config;
use Keboola\GoogleDriveExtractor\Extractor\Extractor;
use Keboola\GoogleDriveExtractor\Extractor\Output;
use Keboola\GoogleDriveExtractor\GoogleDrive\Client;
use PHPUnit\Framework\TestCase;

class ExtractorTest extends TestCase
{
    private Client $googleDriveClient;

    private Extractor $extractor;

    public function setUp(): void
    {
        $api = new RestApi((string) getenv('CLIENT_ID'), (string) getenv('CLIENT_SECRET'));
        $api->setCredentials((string) getenv('ACCESS_TOKEN'), (string) getenv('REFRESH_TOKEN'));
        $this->googleDriveClient = new Client($api);
        $output = new Output('/data', new Config([]));
        $this->extractor = new Extractor($this->googleDriveClient, $output, new Logger());
    }

    public function testColumnToLetter(): void
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
