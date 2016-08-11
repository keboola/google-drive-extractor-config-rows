<?php
/**
 * DataManager.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 29.7.13
 */

namespace Keboola\GoogleDriveExtractor\Extractor;

use Psr\Http\Message\StreamInterface;
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

    public function save(StreamInterface $stream, array $sheet)
    {
        $tmpFilename = $this->writeRawCsv($stream, $sheet);

        $dataProcessor = new Processor($tmpFilename, $sheet);
        $outFilename = $dataProcessor->process();

        $this->createManifest($outFilename, $sheet['outputTable']);

        unlink($tmpFilename);
    }

    protected function writeRawCsv(StreamInterface $stream, array $sheet)
    {
        $outTablesDir = $this->dataDir . '/out/tables';
        if (!is_dir($outTablesDir)) {
            mkdir($outTablesDir, 0777, true);
        }

        $fileName = $outTablesDir . '/' . $sheet['fileId'] . "_" . $sheet['sheetId'] . ".csv";
        $fh = fopen($fileName, 'w+');
        if (!$fh) {
            throw new \Exception("Can't write to file " . $fileName);
        }

        /* @var StreamInterface $data */
        fwrite($fh, $stream->getContents());
        fclose($fh);

        return $fileName;
    }

    public function createManifest($filename, $outputTable)
    {
        $outFilename = $filename . '.manifest';

        $manifestData = [
            'destination' => $outputTable,
            'incremental' => false
        ];

        return file_put_contents($outFilename, Yaml::dump($manifestData));
    }
}
