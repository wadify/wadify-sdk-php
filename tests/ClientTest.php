<?php

namespace Wadify\Test;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use org\bovigo\vfs\vfsStream;
use Wadify\Client;
use Wadify\DependencyInjection\Container;
use Wadify\Token\StorageProvider\FileSystemProvider;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\GuzzleHttp\Client
     */
    private $mockClient;

    /**
     * @expectedException \Wadify\Exception\WadifyBadRequestException
     */
    public function testGetUserThrowsWadifyBadRequestExceptionIf400()
    {
        // Arrange.
        $this->setClientWithMockedResponse([new Response(400)]);

        // Act.
        $this->client->getUser();

        // Assert in arrange.
    }

    /**
     * @expectedException \Wadify\Exception\WadifyAuthenticationException
     */
    public function testGetUserThrowsWadifyBadRequestExceptionIf401()
    {
        // Arrange.
        $this->setClientWithMockedResponse([new Response(401)]);

        // Act.
        $this->client->getUser();

        // Assert in arrange.
    }

    /**
     * @expectedException \Wadify\Exception\WadifyAuthorizationException
     */
    public function testGetUserThrowsWadifyBadRequestExceptionIf403()
    {
        // Arrange.
        $this->setClientWithMockedResponse([new Response(403)]);

        // Act.
        $this->client->getUser();

        // Assert in arrange.
    }

    public function testGetUserReturnAValidResponseFromTheRequest()
    {
        // Arrange.
        $expected = ['foo' => 'bar'];
        $this->setClientWithMockedResponse([
            new Response(200, [], json_encode($expected)),
            $this->getAuthTokenResponse(),
        ]);

        // Act.
        $actual = $this->client->getUser();

        // Assert.
        $this->assertEquals($expected, $actual);
    }

    public function testGetTransactionsReturnAValidResponseFromTheRequest()
    {
        // Arrange.
        $expected = ['foo' => 'bar'];
        $this->setClientWithMockedResponse([
            new Response(200, [], '{"foo": "bar", "_links": {"rel1": {"href": "fake-uri"}}}'),
            $this->getAuthTokenResponse(),
        ]);

        // Act.
        $actual = $this->client->getTransactions();

        // Assert.
        $this->assertEquals($expected, $actual);
    }

    public function testGetTransactionReturnAValidResponseFromTheRequest()
    {
        // Arrange.
        $id = 'foo-bar';
        $expected = ['foo' => 'bar'];
        $this->setClientWithMockedResponse([
            new Response(200, [], '{"foo": "bar", "_links": {"rel1": {"href": "fake-uri"}}}'),
            $this->getAuthTokenResponse(),
        ]);

        // Act.
        $actual = $this->client->getTransaction($id);

        // Assert.
        $this->assertEquals($expected, $actual);
    }

    public function testAbortTransactionReturnAValidResponseFromTheRequest()
    {
        // Arrange.
        $id = 'foo-bar';
        $expected = ['foo' => 'bar'];
        $this->setClientWithMockedResponse([
            new Response(200, [], '{"foo": "bar", "_links": {"rel1": {"href": "fake-uri"}}}'),
            $this->getAuthTokenResponse(),
        ]);

        // Act.
        $actual = $this->client->abortTransaction($id);

        // Assert.
        $this->assertEquals($expected, $actual);
    }

    public function testCreateTransactionReturnAValidResponseFromTheRequest()
    {
        // Arrange.
        $expected = ['foo' => 'bar'];
        $data = [];
        $this->setClientWithMockedResponse([
            new Response(200, [], '{"foo": "bar", "_links": {"rel1": {"href": "fake-uri"}}}'),
            $this->getAuthTokenResponse(),
        ]);

        // Act.
        $actual = $this->client->createTransaction($data);

        // Assert.
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return Response
     */
    private function getAuthTokenResponse()
    {
        $datetime = new \DateTime('tomorrow');
        $response = [
            'access_token' => 'foo',
            'token_type' => 'https://api.wadify.com/grants/apikey',
            'expires' => $datetime->getTimestamp(),
            'refresh_token' => 'foo-bar',
        ];

        return new Response(200, [], json_encode($response));
    }

    /**
     * @param array $responses
     */
    protected function setClientWithMockedResponse(array $responses)
    {
        $mock = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        $this->mockClient = new GuzzleClient(['handler' => $handler]);

        Container::set('guzzle_client', $this->mockClient);
        $this->client = new Client([
            'apiKey' => 'foo',
            'clientId' => 'bar',
            'clientSecret' => 'foo-bar',
            'token' => [
                'provider' => FileSystemProvider::class,
                'args' => [vfsStream::url('test-wadify-client').'/token.json'],
            ],
        ]);
    }

    /**
     *
     */
    protected function setUp()
    {
        vfsStream::setup('test-wadify-client', 0777);
    }
}
