<?php

namespace Wadify\Token;

class Token
{
    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @var string
     */
    protected $expires;

    /**
     * @var string
     */
    protected $refreshToken;

    /**
     * Token constructor.
     *
     * @param string $accessToken
     * @param string $expires
     * @param string $refreshToken
     */
    public function __construct($accessToken, $expires, $refreshToken)
    {
        $this->accessToken = $accessToken;
        $this->expires = $expires;
        $this->refreshToken = $refreshToken;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @return string
     */
    public function getExpires()
    {
        return $this->expires;
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return json_encode([
            'accessToken' => $this->accessToken,
            'expires' => $this->expires,
            'refreshToken' => $this->refreshToken,
        ]);
    }
}