<?php

declare(strict_types=1);

namespace Keboola\GoogleDriveExtractor\Tests;

use Keboola\Csv\CsvWriter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class FunctionalTest extends BaseTest
{
    private string $dataPath = '/tmp/data-test';

    public function testRun(): void
    {
        $process = $this->runProcess();
        $this->assertEquals(0, $process->getExitCode(), $process->getErrorOutput());

        $fileId = $this->config['parameters']['sheets'][0]['fileId'];
        $sheetId = $this->config['parameters']['sheets'][0]['sheetId'];

        $this->assertFileEqualsIgnoringCase(
            $this->testFilePath,
            $this->dataPath . '/out/tables/' . $this->getOutputFileName($fileId, $sheetId)
        );
    }

    public function testRunEmptyFile(): void
    {
        $emptyFilePath = __DIR__ . '/data/in/empty.csv';
        touch($emptyFilePath);

        $this->testFile = $this->prepareTestFile($emptyFilePath, 'empty');
        $this->config = $this->makeConfig($this->testFile);

        $process = $this->runProcess();
        $this->assertEquals(0, $process->getExitCode(), $process->getErrorOutput());

        $fileId = $this->config['parameters']['sheets'][0]['fileId'];
        $sheetId = $this->config['parameters']['sheets'][0]['sheetId'];

        $outputFilepath = $this->dataPath . '/out/tables/' . $this->getOutputFileName($fileId, $sheetId);
        $this->assertFileDoesNotExist($outputFilepath);
        $this->assertFileDoesNotExist($outputFilepath . '.manifest');

        unlink($emptyFilePath);
    }

    public function testSanitizeHeader(): void
    {
        $filePath = __DIR__ . '/data/in/sanitize.csv';
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

    public function testDoNotSanitizeHeader(): void
    {
        $headerLine = '"Weird-[]./;-_*Chars","Second column","# poops per day"';
        $filePath = __DIR__ . '/data/in/not_sanitize.csv';
        touch($filePath);
        file_put_contents($filePath, $headerLine);

        $this->testFile = $this->prepareTestFile($filePath, 'not_sanitize');
        $this->config = $this->makeConfig($this->testFile);

        // leave the header as is
        $this->config['parameters']['sheets'][0]['header']['sanitize'] = false;

        $process = $this->runProcess();
        $this->assertEquals(0, $process->getExitCode(), $process->getErrorOutput());

        $fileId = $this->config['parameters']['sheets'][0]['fileId'];
        $sheetId = $this->config['parameters']['sheets'][0]['sheetId'];

        $this->assertEquals(
            $headerLine . PHP_EOL,
            file_get_contents(
                $this->dataPath . '/out/tables/' . $this->getOutputFileName($fileId, $sheetId)
            )
        );

        unlink($filePath);
    }

    public function testMultipleHeader(): void
    {
        $filePath = __DIR__ . '/data/in/multiple_header.csv';
        touch($filePath);
        $csv = new CsvWriter($filePath);
        $this->writeLineToCsvFile($csv, 1, 7);
        $this->writeLineToCsvFile($csv, 2, 5);
        $this->writeLineToCsvFile($csv, 3, 20);
        $this->writeLineToCsvFile($csv, 4, 2);
        $this->writeLineToCsvFile($csv, 5, 7);

        $this->testFileName = 'multiple_header';
        $this->testFile = $this->prepareTestFile($filePath, 'multiple_header');
        $this->config = $this->makeConfig($this->testFile);

        // leave the header as is
        $this->config['parameters']['sheets'][0]['header']['rows'] = 2;

        $process = $this->runProcess();
        $this->assertEquals(0, $process->getExitCode(), $process->getErrorOutput());

        $fileId = $this->config['parameters']['sheets'][0]['fileId'];
        $sheetId = $this->config['parameters']['sheets'][0]['sheetId'];

        $this->assertFileEquals(
            __DIR__ . '/data/expectedFiles/multiple_header.csv',
            $this->dataPath . '/out/tables/' . $this->getOutputFileName($fileId, $sheetId)
        );
    }

    private function runProcess(): Process
    {
        $fs = new Filesystem();
        $fs->remove($this->dataPath);
        $fs->mkdir($this->dataPath);
        $fs->mkdir($this->dataPath . '/out/tables');

        $yaml = new Yaml();
        file_put_contents($this->dataPath . '/config.yml', $yaml->dump($this->config));

        $process = Process::fromShellCommandline(sprintf('php run.php --data=%s', $this->dataPath));
        $process->run();

        return $process;
    }

    private function writeLineToCsvFile(CsvWriter $csvWriter, int $rowNumber, int $countColumns): void
    {
        $headerLine = [];
        for ($j = 0; $j < $countColumns; $j++) {
            $headerLine[] = sprintf('Col_%s_%s', $rowNumber, $j);
        }
        $csvWriter->writeRow($headerLine);
    }
}
