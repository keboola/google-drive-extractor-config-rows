<?php

declare(strict_types=1);

namespace Keboola\GoogleDriveExtractor;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Keboola\Component\BaseComponent;
use Keboola\Component\UserException;
use Keboola\Google\ClientBundle\Google\RestApi;
use Keboola\GoogleDriveExtractor\Configuration\Config;
use Keboola\GoogleDriveExtractor\Configuration\ConfigDefinition;
use Keboola\GoogleDriveExtractor\Exception\ApplicationException;
use Keboola\GoogleDriveExtractor\Extractor\Extractor;
use Keboola\GoogleDriveExtractor\Extractor\Output;
use Keboola\GoogleDriveExtractor\GoogleDrive\Client;

class Component extends BaseComponent
{
    protected function run(): void
    {
        $extractor = new Extractor(
            new Client($this->getGoogleRestApi()),
            new Output($this->getDataDir(), $this->getConfig()),
            $this->getLogger()
        );

        try {
            $extractor->run($this->getConfig());
        } catch (RequestException $exception) {
            $this->handleException($exception);
        }
    }

    public function getConfig(): Config
    {
        /** @var Config $config */
        $config = parent::getConfig();
        return $config;
    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }

    private function getGoogleRestApi(): RestApi
    {
        $tokenData = json_decode($this->getConfig()->getOAuthApiData(), true);

        return new RestApi(
            $this->getConfig()->getOAuthApiAppKey(),
            $this->getConfig()->getOAuthApiAppSecret(),
            $tokenData['access_token'],
            $tokenData['refresh_token']
        );
    }

    private function handleException(RequestException $exception): void
    {
        /** @var Response $response */
        $response = $exception->getResponse();

        switch ($exception->getCode()) {
            case 400:
                throw new UserException($exception->getMessage());
            case 401:
                throw new UserException(
                    'Expired or wrong credentials, please reauthorize.',
                    $exception->getCode(),
                    $exception
                );
            case 403:
                if (strtolower($response->getReasonPhrase()) === 'forbidden') {
                    $this->getLogger()->warning("You don't have access to Google Drive resource.");
                }
                throw new UserException(
                    sprintf('Reason: "%s"', $response->getReasonPhrase()),
                    $exception->getCode(),
                    $exception
                );
            case 503:
                throw new UserException(
                    sprintf('Google API error: "%s"', $exception->getMessage()),
                    $exception->getCode(),
                    $exception
                );
            default:
                throw new ApplicationException(
                    $exception->getMessage(),
                    500,
                    $exception,
                    [
                        'response' => $response->getBody()->getContents(),
                    ]
                );
        }
    }
}
