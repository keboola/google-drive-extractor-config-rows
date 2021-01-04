<?php

declare(strict_types=1);

namespace Keboola\GoogleDriveExtractor\Tests;

use Keboola\Google\ClientBundle\Google\RestApi;
use Keboola\GoogleDriveExtractor\GoogleDrive\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

abstract class BaseTest extends TestCase
{
    private Client $googleDriveApi;

    protected string $testFilePath = __DIR__ . '/data/in/titanic.csv';

    protected string $testFileName = 'titanic';

    protected array $testFile;

    protected array $config;

    public function setUp(): void
    {
        $this->googleDriveApi = new Client(
            new RestApi(
                (string) getenv('CLIENT_ID'),
                (string) getenv('CLIENT_SECRET'),
                (string) getenv('ACCESS_TOKEN'),
                (string) getenv('REFRESH_TOKEN')
            )
        );
        $this->testFile = $this->prepareTestFile($this->testFilePath, $this->testFileName);
        $this->config = $this->makeConfig($this->testFile);
    }

    protected function prepareTestFile(string $path, string $name): array
    {
        $file = $this->googleDriveApi->createFile($path, $name);
        return $this->googleDriveApi->getSpreadsheet($file['id']);
    }

    protected function makeConfig(array $testFile): array
    {
        $config = Yaml::parse((string) file_get_contents(__DIR__ . '/data/config.yml'));
        $config['parameters']['data_dir'] = __DIR__ . '/data';
        $config['authorization']['oauth_api']['credentials'] = [
            'appKey' => getenv('CLIENT_ID'),
            '#appSecret' => getenv('CLIENT_SECRET'),
            '#data' => json_encode(
                [
                'access_token' => getenv('ACCESS_TOKEN'),
                'refresh_token' => getenv('REFRESH_TOKEN'),
                ]
            ),
        ];
        $config['parameters']['sheets'][0] = [
            'id' => 0,
            'fileId' => $testFile['spreadsheetId'],
            'fileTitle' => $testFile['properties']['title'],
            'sheetId' => $testFile['sheets'][0]['properties']['sheetId'],
            'sheetTitle' => $testFile['sheets'][0]['properties']['title'],
            'outputTable' => $this->testFileName,
            'enabled' => true,
        ];

        return $config;
    }

    public function tearDown(): void
    {
        try {
            $this->googleDriveApi->deleteFile($this->testFile['id']);
        } catch (\Throwable $e) {
        }
    }

    protected function getOutputFileName(string $fileId, int $sheetId): string
    {
        return $fileId . '_' . (string) $sheetId . '.csv';
    }
}
