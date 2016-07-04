<?php

namespace Wadify\Test\Token;


use Wadify\Token\Token;

class TokenTest extends \PHPUnit_Framework_TestCase
{

    public function testGetters()
    {
        // Arrange.
        $accessToken = 'foo';
        $expires = 'bar';
        $refreshToken = 'bar-foo';

        // Act.
        $token = new Token($accessToken, $expires, $refreshToken);

        // Assert
        $this->assertEquals($accessToken, $token->getAccessToken());
        $this->assertEquals($expires, $token->getExpires());
        $this->assertEquals($refreshToken, $token->getRefreshToken());
    }

    public function testToStringReturnsAValidJson()
    {
        // Arrange.
        $accessToken = 'foo';
        $expires = 'bar';
        $refreshToken = 'bar-foo';

        // Act.
        $token = new Token($accessToken, $expires, $refreshToken);

        // Assert.
        $this->assertJson((string)$token);
    }
}
