<?php

namespace Wadify\Test\Token\StorageProvider;

use org\bovigo\vfs\vfsStream;
use Wadify\Token\StorageProvider\FileSystemProvider;
use Wadify\Token\Token;

class FileSystemProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FileSystemProvider
     */
    private $fileSystemProvider;

    /**
     * @var string
     */
    private $fakeDirectory = 'test-wadify-path';

    /**
     * @expectedException \Wadify\Token\TokenNotFoundException
     */
    public function testGetThrowsAnNotFoundExceptionIfTheTokenIsNotPresent()
    {
        // No Arrange needed.

        // Act.
        $this->fileSystemProvider->get();

        // Assert in annotation.
    }

    /**
     * @expectedException \Wadify\Token\TokenNotValidException
     */
    public function testGetThrowsAnNotValidTokenExceptionIfTokeContentNotValid()
    {
        // Arrange.
        file_put_contents($this->getFakePath(), '{}');

        // Act.
        $this->fileSystemProvider->get();

        // Assert in annotation.
    }

    public function testGetReturnsAValidToken()
    {
        // Arrange.
        $accessToken = 'foo';
        $refreshToken = 'foo-bar';
        $expires = 'bar';
        $value = ['accessToken' => $accessToken, 'expires' => $expires, 'refreshToken' => $refreshToken];
        file_put_contents($this->getFakePath(), json_encode($value));

        // Act.
        $token = $this->fileSystemProvider->get();

        // Assert.
        $this->assertEquals($accessToken, $token->getAccessToken());
        $this->assertEquals($refreshToken, $token->getRefreshToken());
        $this->assertEquals($expires, $token->getExpires());
    }

    public function testSetSavesTheTokenInOneFile()
    {
        // Arrange.
        $accessToken = 'foo';
        $refreshToken = 'foo-bar';
        $expires = 'bar';
        $token = new Token($accessToken, $expires, $refreshToken);

        // Act.
        $this->fileSystemProvider->set($token);

        // Assert.
        $this->assertFileExists($this->getFakePath());
    }

    public function testGetRetrievesTheRightTokenFromSet()
    {
        // Arrange.
        $accessToken = 'foo';
        $refreshToken = 'foo-bar';
        $expires = 'bar';
        $token = new Token($accessToken, $expires, $refreshToken);
        $this->fileSystemProvider->set($token);

        // Act.
        $token = $this->fileSystemProvider->get();


        // Assert.
        $this->assertEquals($accessToken, $token->getAccessToken());
        $this->assertEquals($refreshToken, $token->getRefreshToken());
        $this->assertEquals($expires, $token->getExpires());
    }

    /**
     * @expectedException \Wadify\Token\TokenNotStoredException
     */
    public function testSetThrowsAnExceptionIfFileCannotBeStored()
    {
        // Arrange.
        $fileSystemProvider = new FileSystemProvider('/fake/path/foo.json');

        // Act.
        $fileSystemProvider->set(new Token('', '', ''));

        // Assert in annotation.
    }

    /**
     * @return string
     */
    private function getFakePath()
    {
        return vfsStream::url($this->fakeDirectory) . '/test.json';
    }

    /**
     *
     */
    protected function setUp()
    {
        vfsStream::setup($this->fakeDirectory, 0777);
        $this->fileSystemProvider = new FileSystemProvider($this->getFakePath());
    }
}
