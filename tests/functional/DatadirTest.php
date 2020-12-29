<?php

declare(strict_types=1);

namespace Keboola\GoogleDriveExtractor\FunctionalTests;

use Keboola\DatadirTests\DatadirTestCase;
use Keboola\DatadirTests\DatadirTestSpecificationInterface;
use Keboola\Google\ClientBundle\Google\RestApi;
use Keboola\GoogleDriveExtractor\GoogleDrive\Client;
use Keboola\Temp\Temp;
use Symfony\Component\Finder\Finder;
use \Throwable;

class DatadirTest extends DatadirTestCase
{
    /**
     * @dataProvider provideDatadirSpecifications
     */
    public function testDatadir(DatadirTestSpecificationInterface $specification): void
    {
        $tempDatadir = $this->getTempDatadir($specification);

        $googleDriveApi = new Client(
            new RestApi(
                (string) getenv('CLIENT_ID'),
                (string) getenv('CLIENT_SECRET'),
                (string) getenv('ACCESS_TOKEN'),
                (string) getenv('REFRESH_TOKEN')
            )
        );

        $testFileInfo = $this->prepareTestFile(
            $googleDriveApi,
            (string) $specification->getSourceDatadirDirectory()
        );

        $this->prepareConfigFile($tempDatadir, $testFileInfo);

        $process = $this->runScript($tempDatadir->getTmpFolder());

        $this->removeTestFile($googleDriveApi, $testFileInfo['spreadsheetId']);

        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
    }

    private function prepareTestFile(Client $googleDriveApi, string $sourceDatadir): array
    {
        $finder = new Finder();
        $finder->in(sprintf('%s/setup/files/', $sourceDatadir));
        $finder->files();

        $items = iterator_to_array($finder->getIterator());

        /** @var \SplFileInfo $item */
        $item = current($items);
        $file = $googleDriveApi->createFile(
            $item->getPathname(),
            $item->getBasename('.' . $item->getExtension())
        );
        return $googleDriveApi->getSpreadsheet($file['id']);
    }

    private function prepareConfigFile(Temp $tempDatadir, array $testFileInfo): void
    {
        $configPath = $tempDatadir->getTmpFolder() . '/config.json';

        $content = (string) file_get_contents($configPath);

        $jsonContent = json_decode($content, true);

        $jsonContent['authorization']['oauth_api']['credentials'] = [
            'appKey' => (string) getenv('CLIENT_ID'),
            '#appSecret' => (string) getenv('CLIENT_SECRET'),
            '#data' => json_encode(
                [
                    'access_token' => (string) getenv('ACCESS_TOKEN'),
                    'refresh_token' => (string) getenv('REFRESH_TOKEN'),
                ]
            ),
        ];

        $parameters = $jsonContent['parameters'];

        $jsonContent['parameters'] = array_merge(
            $parameters,
            [
                'fileId' => $parameters['fileId'] ?? $testFileInfo['spreadsheetId'],
                'fileTitle' => $parameters['fileTitle'] ?? $testFileInfo['properties']['title'],
                'sheetId' => $parameters['sheetId'] ?? $testFileInfo['sheets'][0]['properties']['sheetId'],
                'sheetTitle' => $parameters['sheetTitle'] ?? $testFileInfo['sheets'][0]['properties']['title'],
            ]
        );

        file_put_contents($configPath, json_encode($jsonContent));
    }

    private function removeTestFile(Client $googleDriveApi, string $spreadsheetId): void
    {
        try {
            $googleDriveApi->deleteFile($spreadsheetId);
        } catch (Throwable $e) {
        }
    }
}
