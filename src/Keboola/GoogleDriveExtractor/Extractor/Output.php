<?php
/**
 * DataManager.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 29.7.13
 */

namespace Keboola\GoogleDriveExtractor\Extractor;

use Keboola\Csv\CsvFile;
use Keboola\GoogleDriveExtractor\Exception\UserException;
use Symfony\Component\Yaml\Yaml;

class Output
{
    private $dataDir;

    private $outputBucket;

    /** @var CsvFile */
    private $csv;

    private $header;

    public function __construct($dataDir, $outputBucket)
    {
        $this->dataDir = $dataDir;
        $this->outputBucket = $outputBucket;
    }

    /**
     * @param $sheet
     * @return CsvFile
     */
    public function createCsv($sheet)
    {
        $outTablesDir = $this->dataDir . '/out/tables';
        if (!is_dir($outTablesDir)) {
            mkdir($outTablesDir, 0777, true);
        }

        $this->csv = new CsvFile($outTablesDir . '/' . $sheet['fileId'] . "_" . $sheet['sheetId'] . ".csv");
        $this->header = null;

        return $this->csv;
    }

    public function write($data)
    {
        if ($this->header == null) {
            $this->header = $data[0];
        }

        $headerLength = count($this->header);

        foreach ($data as $k => $row) {
            $rowLength = count($row);
            if ($rowLength > $headerLength) {
                throw new UserException(sprintf(
                    "Row %s has more columns (%s) then header (%s).",
                    $k,
                    $rowLength,
                    $headerLength
                ));
            }
            $this->csv->writeRow(array_pad($row, $headerLength, ""));
        }
    }

    /**
     * @param $sheet
     * @return CsvFile
     */
    public function process($sheet)
    {
        $processor = new Processor($this->csv, $sheet);
        return $processor->process();
    }

    public function createManifest($filename, $outputTable)
    {
        $outFilename = $filename . '.manifest';

        $manifestData = [
            'destination' => $this->outputBucket . '.' . $outputTable,
            'incremental' => false
        ];

        return file_put_contents($outFilename, Yaml::dump($manifestData));
    }
}
