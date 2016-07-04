<?php

namespace Wadify\Token\StorageProvider;

use Wadify\Token\Token;
use Wadify\Token\TokenNotFoundException;
use Wadify\Token\TokenNotStoredException;
use Wadify\Token\TokenNotValidException;

class FileSystemProvider implements StorageProviderInterface
{
    /**
     * @var string
     */
    private $path;

    /**
     * FileSystemProvider constructor.
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * @return Token
     * @throws TokenNotFoundException
     * @throws TokenNotValidException
     */
    public function get()
    {
        if (file_exists($this->path)) {
            $arrayToken = json_decode(file_get_contents($this->path), true);

            try {
                return new Token($arrayToken['accessToken'], $arrayToken['expires'], $arrayToken['refreshToken']);
            } catch (\Exception $e) {
                throw new TokenNotValidException();
            }
        }

        throw new TokenNotFoundException();
    }

    /**
     * @param Token $token
     *
     * @return mixed
     * @throws TokenNotStoredException
     */
    public function set(Token $token)
    {
        try {
            file_put_contents($this->path, (string)$token);
        } catch (\Exception $e) {
            throw new TokenNotStoredException($e);
        }
    }
}
