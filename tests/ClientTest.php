<?php

namespace Wadify\Test;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Wadify\Client;
use Wadify\DependencyInjection\Container;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    private $headers = [
        'x-auth-apikey' => 'foo',
        'content-type' => 'application/json',
        'accept' => 'application/json',
    ];

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
        $response = new Response(400);
        $request = new Request('bar', 'foo');
        $clientException = new ClientException('foo', $request, $response);
        $this->mockClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', "/api/0.0.1/user", ['headers' => $this->headers])
            ->willThrowException($clientException);

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
        $response = new Response(401);
        $request = new Request('bar', 'foo');
        $clientException = new ClientException('foo', $request, $response);
        $this->mockClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', "/api/0.0.1/user", ['headers' => $this->headers])
            ->willThrowException($clientException);

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
        $response = new Response(403);
        $request = new Request('bar', 'foo');
        $clientException = new ClientException('foo', $request, $response);
        $this->mockClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', "/api/0.0.1/user", ['headers' => $this->headers])
            ->willThrowException($clientException);

        // Act.
        $this->client->getUser();

        // Assert in arrange.
    }

    public function testGetUserReturnAValidResponseFromTheRequest()
    {
        // Arrange.
        $expected = ['foo' => 'bar'];
        $response = new Response(200, [], '{"foo": "bar", "_links": {"rel1": {"href": "fake-uri"}}}');
        $this->mockClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', "/api/0.0.1/user", ['headers' => $this->headers])
            ->willReturn($response);

        // Act.
        $actual = $this->client->getUser();

        // Assert.
        $this->assertEquals($expected, $actual);
    }

    public function testGetTransactionsReturnAValidResponseFromTheRequest()
    {
        // Arrange.
        $expected = ['foo' => 'bar'];
        $response = new Response(200, [], '{"foo": "bar", "_links": {"rel1": {"href": "fake-uri"}}}');
        $this->mockClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', "/api/0.0.1/transactions", ['headers' => $this->headers])
            ->willReturn($response);

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
        $response = new Response(200, [], '{"foo": "bar", "_links": {"rel1": {"href": "fake-uri"}}}');
        $this->mockClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', "/api/0.0.1/transactions/{$id}", ['headers' => $this->headers])
            ->willReturn($response);

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
        $response = new Response(200, [], '{"foo": "bar", "_links": {"rel1": {"href": "fake-uri"}}}');
        $this->mockClient
            ->expects($this->once())
            ->method('request')
            ->with('PATCH', "/api/0.0.1/transactions/{$id}/abort", ['headers' => $this->headers])
            ->willReturn($response);

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
        $response = new Response(200, [], '{"foo": "bar", "_links": {"rel1": {"href": "fake-uri"}}}');
        $this->mockClient
            ->expects($this->once())
            ->method('request')
            ->with('POST', "/api/0.0.1/transactions", ['headers' => $this->headers, 'json' => $data])
            ->willReturn($response);

        // Act.
        $actual = $this->client->createTransaction($data);

        // Assert.
        $this->assertEquals($expected, $actual);
    }

    protected function setUp()
    {
        $apiKey = 'foo';
        $this->mockClient = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock();
        Container::set('guzzle_client', $this->mockClient);
        $this->client = new Client($apiKey, []);
    }
}
