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
        $this->assertEquals(0, $process->getExitCode(), $process->getErrorOutput());

        $fileId = $this->config['parameters']['sheets'][0]['fileId'];
        $sheetId = $this->config['parameters']['sheets'][0]['sheetId'];

        $this->assertFileEquals(
            $this->testFilePath,
            $this->dataPath . '/out/tables/' . $this->getOutputFileName($fileId, $sheetId),
            "",
            true
        );
    }

    public function testRunEmptyFile()
    {
        $emptyFilePath = ROOT_PATH . '/tests/data/in/empty.csv';
        touch($emptyFilePath);

        $this->testFile = $this->prepareTestFile($emptyFilePath, 'empty');
        $this->config = $this->makeConfig($this->testFile);

        $process = $this->runProcess();
        $this->assertEquals(0, $process->getExitCode(), $process->getErrorOutput());

        $fileId = $this->config['parameters']['sheets'][0]['fileId'];
        $sheetId = $this->config['parameters']['sheets'][0]['sheetId'];

        $outputFilepath = $this->dataPath . '/out/tables/' . $this->getOutputFileName($fileId, $sheetId);
        $this->assertFileNotExists($outputFilepath);
        $this->assertFileNotExists($outputFilepath . '.manifest');

        unlink($emptyFilePath);
    }

    public function testSanitizeHeader()
    {
        $filePath = ROOT_PATH . '/tests/data/in/sanitize.csv';
        touch($filePath);
        file_put_contents($filePath, '"Weird-[]./;-_*Chars", "Second column", "# poops per day"');

        $this->testFile = $this->prepareTestFile($filePath, 'sanitize');
        $this->config = $this->makeConfig($this->testFile);

        $process = $this->runProcess();
        $this->assertEquals(0, $process->getExitCode(), $process->getErrorOutput());

        $fileId = $this->config['parameters']['sheets'][0]['fileId'];
        $sheetId = $this->config['parameters']['sheets'][0]['sheetId'];

        $this->assertEquals(
            '"Weird_Chars","Second_column","count_poops_per_day"' . PHP_EOL,
            file_get_contents(
                $this->dataPath . '/out/tables/' . $this->getOutputFileName($fileId, $sheetId)
            )
        );

        unlink($filePath);
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
