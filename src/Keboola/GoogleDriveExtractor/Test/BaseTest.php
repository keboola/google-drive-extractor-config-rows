<?php
/**
 * Created by PhpStorm.
 * User: miroslavcillik
 * Date: 10/08/16
 * Time: 16:49
 */

namespace Keboola\GoogleDriveExtractor\Test;

use Keboola\Google\ClientBundle\Google\RestApi;
use Keboola\GoogleDriveExtractor\GoogleDrive\Client;
use Symfony\Component\Yaml\Yaml;

class BaseTest extends \PHPUnit_Framework_TestCase
{
    /** @var Client */
    private $googleDriveApi;

    protected $testFilePath = ROOT_PATH . '/tests/data/in/titanic.csv';

    protected $testFileName = 'titanic';

    protected $testFile;

    protected $config;

    public function setUp()
    {
        $this->googleDriveApi = new Client(new RestApi(
            getenv('CLIENT_ID'),
            getenv('CLIENT_SECRET'),
            getenv('ACCESS_TOKEN'),
            getenv('REFRESH_TOKEN')
        ));
        $this->testFile = $this->prepareTestFile($this->testFilePath, $this->testFileName);
        $this->config = $this->makeConfig($this->testFile);
    }

    protected function prepareTestFile($path, $name)
    {
        $file = $this->googleDriveApi->createFile($path, $name);
        return $this->googleDriveApi->getSpreadsheet($file['id']);
    }

    protected function makeConfig($testFile)
    {
        $config = Yaml::parse(file_get_contents(ROOT_PATH . '/tests/data/config.yml'));
        $config['parameters']['data_dir'] = ROOT_PATH . '/tests/data';
        $config['authorization']['oauth_api']['credentials'] = [
            'appKey' => getenv('CLIENT_ID'),
            '#appSecret' => getenv('CLIENT_SECRET'),
            '#data' => json_encode([
                'access_token' => getenv('ACCESS_TOKEN'),
                'refresh_token' => getenv('REFRESH_TOKEN')
            ])
        ];
        $config['parameters']['sheets'][0] = [
            'id' => 0,
            'fileId' => $testFile['spreadsheetId'],
            'fileTitle' => $testFile['properties']['title'],
            'sheetId' => $testFile['sheets'][0]['properties']['sheetId'],
            'sheetTitle' => $testFile['sheets'][0]['properties']['title'],
            'outputTable' => $this->testFileName,
            'enabled' => true
        ];

        return $config;
    }

    public function tearDown()
    {
        try {
            $this->googleDriveApi->deleteFile($this->testFile['id']);
        } catch (\Exception $e) {
        }
    }

    protected function getOutputFileName($fileId, $sheetId)
    {
        return $fileId . '_' . $sheetId . '.csv';
    }
}
