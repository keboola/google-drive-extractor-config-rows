<?php
/**
 * DataManager.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 29.7.13
 */

namespace Keboola\GoogleDriveExtractor\Extractor;

use Keboola\Csv\CsvFile;
use Symfony\Component\Yaml\Yaml;

class Output
{
    private $dataDir;

    private $outputBucket;

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
        $filename = $outTablesDir . '/' . $sheet['fileId'] . "_" . $sheet['sheetId'] . ".csv";

        return new CsvFile($filename);
    }

    public function write(CsvFile $csv, $data)
    {
        foreach ($data as $row) {
            $csv->writeRow($row);
        }
    }

    /**
     * @param CsvFile $csv
     * @param $sheet
     * @return CsvFile
     */
    public function process(CsvFile $csv, $sheet)
    {
        $processor = new Processor($csv, $sheet);
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
