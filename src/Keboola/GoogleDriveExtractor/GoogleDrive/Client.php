<?php
/**
 * RestApi.php
 *
 * @author: Miroslav Čillík <miro@keboola.com>
 * @created: 26.7.13
 */

namespace Keboola\GoogleDriveExtractor\GoogleDrive;

use Keboola\Google\ClientBundle\Google\RestApi as GoogleApi;

class Client
{
    /** @var GoogleApi */
    protected $api;

    const FILES = 'https://www.googleapis.com/drive/v2/files';

    public function __construct(GoogleApi $api)
    {
        $this->api = $api;
    }

    /**
     * @return GoogleApi
     */
    public function getApi()
    {
        return $this->api;
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Keboola\Google\ClientBundle\Exception\RestApiException
     */
    public function getFile($id)
    {
        $response = $this->api->request(
            self::FILES . '/' . $id,
            'GET'
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param $url
     * @return \Psr\Http\Message\StreamInterface
     * @throws \Keboola\Google\ClientBundle\Exception\RestApiException
     */
    public function export($url)
    {
        $response = $this->api->request(
            $url,
            'GET',
            [
                'Accept' => 'text/csv; charset=UTF-8',
                'GData-Version' => '3.0'
            ]
        );

        return $response->getBody();
    }
}
