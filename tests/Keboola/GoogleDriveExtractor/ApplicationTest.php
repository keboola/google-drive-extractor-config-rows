<?php

/**
 * Created by PhpStorm.
 * User: miroslavcillik
 * Date: 19/04/16
 * Time: 10:59
 */

use GuzzleHttp\Client;
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

        $salesOutputPath = ROOT_PATH . '/tests/data/out/tables/1tep21r8fDJyXJyMAo2KKqBrxaEmqoJuwnQB4Y6gqGBU-10.csv';
        $salesManifestPath = $salesOutputPath . '.manifest';

        $this->assertFileExists($salesOutputPath);
        $this->assertFileExists($salesManifestPath);

        $salesManifest = Yaml::parse(file_get_contents($salesManifestPath));

        foreach ([$salesManifest] as $manifest) {
            $this->assertArrayHasKey('destination', $manifest);
            $this->assertArrayHasKey('incremental', $manifest);
            $this->assertTrue($manifest['incremental']);
            $this->assertArrayHasKey('primary_key', $manifest);
            $this->assertEquals('id', $manifest['primary_key'][0]);
        }

//        $this->assertEquals($this->config['parameters']['outputBucket'] . '.users.csv', $usersManifest['destination']);
//        $this->assertEquals($this->config['parameters']['outputBucket'] . '.profiles.csv', $profilesManifest['destination']);
    }
}
