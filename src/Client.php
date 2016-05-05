<?php

namespace Wadify;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Wadify\DependencyInjection\Container;
use Wadify\Exception\WadifyAuthenticationException;
use Wadify\Exception\WadifyAuthorizationException;
use Wadify\Exception\WadifyBadRequestException;

class Client
{
    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $version;

    /**
     * @var array
     */
    private $links = [];

    /**
     * WadifyClient constructor.
     * Valid options:
     *  - version [default: latest stable version]
     *  - sandbox: [default: false]
     *
     * @param string $apiKey
     * @param array  $options
     */
    public function __construct($apiKey, array $options = array())
    {
        $this->apiKey = $apiKey;
        $options = array_merge($this->getDefaultOptions(), $options);
        $this->version = $options['version'];
        $guzzleClientServiceName = (false === $options['sandbox']) ? 'guzzle_client' : 'guzzle_client_sandbox';
        $this->client = Container::get($guzzleClientServiceName);
    }

    /**
     * @return array
     */
    private function getDefaultOptions()
    {
        return ['version' => '0.0.1', 'sandbox' => false];
    }

    /**
     * @return Response
     */
    public function getUser()
    {
        return $this->request('GET', 'user');
    }

    /**
     * @return Response
     */
    public function getTransactions()
    {
        return $this->request('GET', 'transactions');
    }

    /**
     * @param string $id
     *
     * @return Response
     */
    public function getTransaction($id)
    {
        return $this->request('GET', 'transactions', $id);
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function abortTransaction($id)
    {
        return $this->request('PATCH', 'transactions', $id.'/abort');
    }

    /**
     * @param array $data
     *
     * @return Response
     */
    public function createTransaction(array $data)
    {
        return $this->request('POST', 'transactions', null, ['json' => $data]);
    }

    /**
     * @return array
     */
    private function getDefaultHeaders()
    {
        return [
            'x-auth-apikey' => $this->apiKey,
            'content-type' => 'application/json',
            'accept' => 'application/json',
        ];
    }

    /**
     * @param string $method
     * @param string $action
     * @param string $append
     * @param array  $options
     *
     * @return array
     */
    private function request($method, $action, $append = null, array $options = [])
    {
        $headers = isset($options['headers']) ? $options['headers'] : [];
        $options['headers'] = array_merge($this->getDefaultHeaders(), $headers);

        try {
            $response = $this->client->request($method, $this->getUri($action, $append), $options);
            $body = json_decode($response->getBody(), true);

            if (isset($body['_links'])) {
                $this->links = $body['_links'];
                unset($body['_links']);
            }

            return $body;
        } catch (ClientException $exception) {
            throw $this->getCustomException($exception);
        }
    }

    /**
     * @param string $resource
     * @param string $append
     *
     * @return string
     */
    private function getUri($resource, $append = null)
    {
        if (isset($this->links[$resource])) {
            return $this->links[$resource]['href'];
        }

        $uri = "/api/{$this->version}/{$resource}";

        return (false === is_null($append)) ? "$uri/$append" : $uri;
    }

    /**
     * @param ClientException $e
     *
     * @return RequestException
     */
    private function getCustomException(ClientException $e)
    {
        switch ($e->getCode()) {
            case 400:
                return new WadifyBadRequestException($e->getMessage(), $e->getRequest(), $e->getResponse());
            case 401:
                return new WadifyAuthenticationException($e->getMessage(), $e->getRequest(), $e->getResponse());
            case 403:
                return new WadifyAuthorizationException($e->getMessage(), $e->getRequest(), $e->getResponse());
            default:
                return $e;
        }
    }
}
