<?php

declare(strict_types=1);

namespace Keboola\GoogleDriveExtractor\Extractor;

use Keboola\Csv\CsvWriter;
use Keboola\GoogleDriveExtractor\Configuration\Config;

class Output
{
    private string $dataDir;

    private CsvWriter $csv;

    private ?array $header;

    private Config $config;

    public function __construct(string $dataDir, Config $config)
    {
        $this->dataDir = $dataDir;
        $this->config = $config;
    }

    public function createCsv(): string
    {
        $outTablesDir = $this->dataDir . '/out/tables';
        if (!is_dir($outTablesDir)) {
            mkdir($outTablesDir, 0777, true);
        }

        $filename = $outTablesDir . '/' . $this->config->getOutputTable() . '.csv';
        touch($filename);

        $this->csv = new CsvWriter($filename);
        $this->header = null;

        return $filename;
    }

    public function write(array $data, int $offset): void
    {
        if (!($this->csv instanceof CsvWriter)) {
            return;
        }

        $headerConfig = $this->config->getHeader();
        if ($this->header === null) {
            $headerRowNum = $headerConfig['rows'] - 1;
            $this->header = $data[$headerRowNum];
            $headerLength = $this->getHeaderLength($data, (int) $headerRowNum);
        } else {
            $headerLength = count($this->header);
        }

        foreach ($data as $k => $row) {
            // backward compatibility fix
            if ($headerConfig['rows'] === 1 && $k === 0 && $offset === 1) {
                if (!isset($headerConfig['sanitize']) || $headerConfig['sanitize'] !== false) {
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

    public function createManifest(string $filename): bool
    {
        $outFilename = $filename . '.manifest';

        $manifestData = [
            'incremental' => false,
        ];

        return (bool) file_put_contents($outFilename, json_encode($manifestData));
    }

    protected function normalizeCsvHeader(array $header): array
    {
        foreach ($header as &$col) {
            $col = Utility::sanitize($col);
        }
        return $header;
    }

    private function getHeaderLength(array $data, int $headerRowNum): int
    {
        $headerLength = 0;
        for ($i = 0; $i <= $headerRowNum; $i++) {
            $headerLength = max($headerLength, count($data[$i]));
        }
        return $headerLength;
    }
}
