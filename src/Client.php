<?php

namespace Wadify;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Sainsburys\Guzzle\Oauth2\GrantType\RefreshToken;
use Sainsburys\Guzzle\Oauth2\Middleware\OAuthMiddleware;
use Wadify\DependencyInjection\Container;
use Wadify\Exception\WadifyAuthenticationException;
use Wadify\Exception\WadifyAuthorizationException;
use Wadify\Exception\WadifyBadRequestException;
use Wadify\OAuth2\GrantType\ApiKey;

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
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientSecret;

    /**
     * @var array
     */
    private $links = [];

    /**
     * @var OAuthMiddleware
     */
    private $middleware;

    /**
     * @var array
     */
    private $tokenCache;

    /**
     * @var string
     */
    private $cachePath = '/tmp/wadify/tokencache.json';

    /**
     * WadifyClient constructor.
     * Valid options:
     *  - version [default: latest stable version]
     *  - sandbox: [default: false]
     *
     * @param string $apiKey
     * @param string $clientId
     * @param string $clientSecret
     * @param array $options
     */
    public function __construct($apiKey, $clientId, $clientSecret, array $options = array())
    {
        $this->apiKey = $apiKey;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $options = array_merge($this->getDefaultOptions(), $options);
        $this->version = $options['version'];
        $guzzleClientServiceName = (false === $options['sandbox']) ? 'guzzle_client' : 'guzzle_client_sandbox';

        $stack = HandlerStack::create();
        $this->client = Container::get($guzzleClientServiceName, [
            'config' => [
                'handler' => $stack,
                'auth' => 'oauth2',
                'headers' => ['Accept' => 'application/json']
            ]
        ]);

        $config = [
            ApiKey::CONFIG_APIKEY => '01bc91f4cd20ead7bc26a36b58a6d96e',
            ApiKey::CONFIG_CLIENT_ID => $this->clientId,
            ApiKey::CONFIG_CLIENT_SECRET => $this->clientSecret,
            ApiKey::CONFIG_TOKEN_URL => '/oauth/v2/token',
            ApiKey::CONFIG_AUTH_LOCATION => RequestOptions::BODY,
        ];

        $token = new ApiKey($this->client, $config);
        $refreshToken = new RefreshToken($this->client, $config);
        $this->middleware = new OAuthMiddleware($this->client, $token, $refreshToken);

        if($this->getCachedTokens() !== false){
            $this->middleware->setAccessToken($this->tokenCache['accessToken'], null, $this->tokenCache['expires']);
            $this->middleware->setRefreshToken($this->tokenCache['refreshToken']);
        }

        $stack->push($this->middleware->onBefore());
        $stack->push($this->middleware->onFailure(5));


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
        return $this->request('PATCH', 'transactions', $id . '/abort');
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
            'content-type' => 'application/json',
            'accept' => 'application/json',
        ];
    }

    /**
     * @param string $method
     * @param string $action
     * @param string $append
     * @param array $options
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
            $this->setCachedTokens();
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

    private function setCachedTokens()
    {
            if($this->tokenCache['accessToken'] !== $this->middleware->getAccessToken()->getToken()) {
                $this->tokenCache = [
                    'accessToken' => $this->middleware->getAccessToken()->getToken(),
                    'expires' => $this->middleware->getAccessToken()->getExpires()->getTimestamp(),
                    'refreshToken' => $this->middleware->getRefreshToken()->getToken()
                ];
                if (!is_dir(dirname($this->cachePath))) {
                    mkdir(dirname($this->cachePath), 0777, true);
                }
                file_put_contents($this->cachePath, json_encode($this->tokenCache));
            }
    }

    /**
     * @return array|bool|mixed
     */
    private function getCachedTokens()
    {
        if(file_exists($this->cachePath)){
            $file = file_get_contents($this->cachePath);
            $this->tokenCache = json_decode($file, true);
            return $this->tokenCache;
        }

        return false;
    }
}
