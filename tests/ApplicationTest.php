<?php

declare(strict_types=1);

namespace Keboola\GoogleDriveExtractor\Tests;

use Keboola\GoogleDriveExtractor\Application;
use Keboola\GoogleDriveExtractor\Exception\UserException;
use Symfony\Component\Yaml\Yaml;

class ApplicationTest extends BaseTest
{
    private Application $application;

    public function setUp(): void
    {
        parent::setUp();
        $this->application = new Application($this->config);
    }

    public function testAppRun(): void
    {
        $this->application->run();

        $outputPath = sprintf(
            '%s/data/out/tables/%s_%s.csv',
            __DIR__,
            $this->testFile['spreadsheetId'],
            $this->testFile['sheets'][0]['properties']['sheetId']
        );

        $manifestPath = $outputPath . '.manifest';
        $manifest = Yaml::parse((string) file_get_contents($manifestPath));

        $this->assertArrayHasKey('destination', $manifest);
        $this->assertArrayHasKey('incremental', $manifest);
        $this->assertFalse($manifest['incremental']);

        $outputTableId = sprintf(
            '%s.%s',
            $this->config['parameters']['outputBucket'],
            $this->config['parameters']['sheets'][0]['outputTable']
        );

        $this->assertEquals($outputTableId, $manifest['destination']);
    }

    public function testInvalidSpreadsheetId(): void
    {
        $this->testFile['sheets'][0]['properties']['sheetId'] = 18293729;
        $this->config = $this->makeConfig($this->testFile);
        $this->application = new Application($this->config);

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('Sheet id "18293729" not found');
        $this->application->run();
    }
}
