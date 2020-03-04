<?php

declare(strict_types=1);

namespace Keboola\GoogleDriveExtractor\Extractor;

use Keboola\Csv\CsvWriter;
use Symfony\Component\Yaml\Yaml;

class Output
{
    /** @var string */
    private $dataDir;

    /** @var string */
    private $outputBucket;

    /** @var CsvWriter */
    private $csv;

    /** @var array|null */
    private $header;

    /** @var array */
    private $sheetCfg;

    public function __construct(string $dataDir, string $outputBucket)
    {
        $this->dataDir = $dataDir;
        $this->outputBucket = $outputBucket;
    }

    public function createCsv(array $sheet): string
    {
        $outTablesDir = $this->dataDir . '/out/tables';
        if (!is_dir($outTablesDir)) {
            mkdir($outTablesDir, 0777, true);
        }

        $filename = $outTablesDir . '/' . $sheet['fileId'] . '_' . $sheet['sheetId'] . '.csv';
        touch($filename);

        $this->csv = new CsvWriter($filename);
        $this->header = null;
        $this->sheetCfg = $sheet;

        return $filename;
    }

    public function write(array $data, int $offset): void
    {
        if ($this->csv === null) {
            return;
        }

        if ($this->header === null) {
            $headerRowNum = $this->sheetCfg['header']['rows'] - 1;
            $this->header = $data[$headerRowNum];
        }

        $headerLength = count($this->header);

        foreach ($data as $k => $row) {
            // backward compatibility fix
            if ($this->sheetCfg['header']['rows'] === 1 && $k === 0 && $offset === 1) {
                if (!isset($this->sheetCfg['header']['sanitize']) || $this->sheetCfg['header']['sanitize'] !== false) {
                    $row = $this->normalizeCsvHeader($row);
                }
            }
            $rowLength = count($row);
            if ($rowLength > $headerLength) {
                $row = array_slice($row, 0, $headerLength);
            }
            $this->csv->writeRow(array_pad($row, $headerLength, ''));
        }
    }

    public function createManifest(string $filename, string $outputTable): bool
    {
        $outFilename = $filename . '.manifest';

        $manifestData = [
            'destination' => $this->outputBucket . '.' . $outputTable,
            'incremental' => false,
        ];

        return (bool) file_put_contents($outFilename, Yaml::dump($manifestData));
    }

    protected function normalizeCsvHeader(array $header): array
    {
        foreach ($header as &$col) {
            $col = Utility::sanitize($col);
        }
        return $header;
    }
}
