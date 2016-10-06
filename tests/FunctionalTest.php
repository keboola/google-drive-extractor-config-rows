<?php
/**
 * Created by PhpStorm.
 * User: miroslavcillik
 * Date: 10/08/16
 * Time: 16:45
 */
namespace Keboola\GoogleDriveExtractor\Tests;

use Keboola\Csv\CsvFile;
use Keboola\GoogleDriveExtractor\Test\BaseTest;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class FunctionalTest extends BaseTest
{
    private $dataPath = '/tmp/data-test';

    public function testRun()
    {
        $process = $this->runProcess();
        $this->assertEquals(0, $process->getExitCode());

        $fileId = $this->config['parameters']['sheets'][0]['fileId'];
        $sheetId = $this->config['parameters']['sheets'][0]['sheetId'];

        $this->assertFileEquals(
            $this->testFilePath,
            $this->dataPath . '/out/tables/' . $this->getOutputFileName($fileId, $sheetId),
            "",
            true
        );
    }

    /**
     * @return Process
     */
    private function runProcess()
    {
        $fs = new Filesystem();
        $fs->remove($this->dataPath);
        $fs->mkdir($this->dataPath);
        $fs->mkdir($this->dataPath . '/out/tables');

        $yaml = new Yaml();
        file_put_contents($this->dataPath . '/config.yml', $yaml->dump($this->config));

        $process = new Process(sprintf('php run.php --data=%s', $this->dataPath));
        $process->run();

        return $process;
    }
}
