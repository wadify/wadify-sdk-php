<?php

namespace Wadify;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use Sainsburys\Guzzle\Oauth2\GrantType\RefreshToken;
use Sainsburys\Guzzle\Oauth2\Middleware\OAuthMiddleware;
use Wadify\DependencyInjection\Container;
use Wadify\Exception\WadifyAuthenticationException;
use Wadify\Exception\WadifyAuthorizationException;
use Wadify\Exception\WadifyBadRequestException;
use Wadify\OAuth2\GrantType\ApiKey;
use Wadify\Token\StorageProvider\FileSystemProvider;
use Wadify\Token\StorageProvider\StorageProviderInterface;
use Wadify\Token\Token;
use Wadify\Token\TokenNotFoundException;

class Client
{
    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @var string
     */
    private $version;

    /**
     * @var array
     */
    private $links = [];

    /**
     * @var OAuthMiddleware
     */
    private $middleware;

    /**
     * @var StorageProviderInterface
     */
    private $tokenStorageProvider;

    /**
     * @var array
     */
    private $defaultOptions = [
        'version' => '1',
        'sandbox' => false,
        'token' => [
            'provider' => FileSystemProvider::class,
            'args' => ['/tmp/wadify/token.json'],
        ],
    ];

    /**
     * WadifyClient constructor.
     * Valid options:
     *  - apiKey [REQUIRED]
     *  - clientId [REQUIRED]
     *  - clientSecret [REQUIRED]
     *  - version [default: latest stable version]
     *  - sandbox: [default: false]
     *  - token: [
     *      - provider: [default: Wadify\Token\StorageProvider\FileSystemProvider]
     *      - args: [default: [/tmp/wadify/token.json]]
     *    ]
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->validateOptions($options);
        $options = array_merge($this->defaultOptions, $options);
        $this->version = $options['version'];
        $guzzleClientServiceName = (false === $options['sandbox']) ? 'guzzle_client' : 'guzzle_client_sandbox';
        $this->tokenStorageProvider = $this->getTokenStorageProviderFromOptions($options['token']);

        $stack = HandlerStack::create();
        $this->client = Container::get($guzzleClientServiceName, [
            'config' => [
                'handler' => $stack,
                'auth' => 'oauth2',
                'headers' => ['Accept' => $this->getAcceptHeader()],
            ],
        ]);

        $this->pushStack($options, $stack);
    }

    /**
     * @return string
     */
    private function getAcceptHeader()
    {
        return "application/json;version={$this->version}";
    }

    /**
     * @param array $options
     *
     * @throws \InvalidArgumentException
     */
    private function validateOptions(array $options)
    {
        $requiredOptions = array('apiKey', 'clientId', 'clientSecret');
        foreach ($requiredOptions as $option) {
            if (false === key_exists($option, $options)) {
                throw new \InvalidArgumentException($option);
            }
        }
    }

    /**
     * @param array $options
     *
     * @return StorageProviderInterface
     */
    private function getTokenStorageProviderFromOptions(array $options)
    {
        $reflectionClass = new \ReflectionClass($options['provider']);

        return $reflectionClass->newInstanceArgs($options['args']);
    }

    /**
     * @return array
     */
    public function getUser()
    {
        return $this->request('GET', 'user');
    }

    /**
     * @return array
     */
    public function getTransactions()
    {
        return $this->request('GET', 'transactions');
    }

    /**
     * @param string $id
     *
     * @return array
     */
    public function getTransaction($id)
    {
        return $this->request('GET', 'transactions', $id);
    }

    /**
     * @param string $id
     *
     * @return array
     */
    public function abortTransaction($id)
    {
        return $this->request('PATCH', 'transactions', $id);
    }

    /**
     * @param array $data
     *
     * @return array
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
        return ['Content-Type' => 'application/json', 'Accept' => $this->getAcceptHeader()];
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
            $this->tokenStorageProvider->set($this->getToken());

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

        $uri = "/{$resource}";

        return (false === is_null($append)) ? "{$uri}/{$append}" : $uri;
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

    /**
     * @return Token
     */
    private function getToken()
    {
        return new Token(
            $this->middleware->getAccessToken()->getToken(),
            $this->middleware->getAccessToken()->getExpires()->getTimestamp(),
            $this->middleware->getRefreshToken()->getToken()
        );
    }

    /**
     * @param array        $options
     * @param HandlerStack $stack
     */
    private function pushStack($options, $stack)
    {
        $options = [
            ApiKey::CONFIG_APIKEY => $options['apiKey'],
            ApiKey::CONFIG_CLIENT_ID => $options['clientId'],
            ApiKey::CONFIG_CLIENT_SECRET => $options['clientSecret'],
            ApiKey::CONFIG_TOKEN_URL => '/oauth/v2/token',
            ApiKey::CONFIG_AUTH_LOCATION => RequestOptions::BODY,
        ];

        $token = new ApiKey($this->client, $options);
        $refreshToken = new RefreshToken($this->client, $options);
        $this->middleware = new OAuthMiddleware($this->client, $token, $refreshToken);

        try {
            $token = $this->tokenStorageProvider->get();
            $this->middleware->setAccessToken($token->getAccessToken(), null, $token->getExpires());
            $this->middleware->setRefreshToken($token->getRefreshToken());
        } catch (TokenNotFoundException $e) {
        }

        $stack->push($this->middleware->onBefore());
        $stack->push($this->middleware->onFailure(5));
    }
}
