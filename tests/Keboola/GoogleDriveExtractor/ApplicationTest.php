<?php
/**
 * Created by PhpStorm.
 * User: miroslavcillik
 * Date: 19/04/16
 * Time: 10:59
 */

namespace Keboola\GoogleDriveExtractor\Tests;

use Keboola\GoogleDriveExtractor\Application;
use Keboola\GoogleDriveExtractor\Test\BaseTest;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class ApplicationTest extends BaseTest
{
    /** @var Application */
    private $application;

    public function setUp()
    {
        parent::setUp();
        $this->application = new Application($this->config);
    }

    public function testAppRun()
    {
        $this->application->run();

        $outputPath = sprintf(
            '%s/tests/data/out/tables/%s_%s.csv',
            ROOT_PATH,
            $this->testFile['spreadsheetId'],
            $this->testFile['sheets'][0]['properties']['sheetId']
        );

        $manifestPath = $outputPath . '.manifest';
        $manifest = Yaml::parse(file_get_contents($manifestPath));

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
}
