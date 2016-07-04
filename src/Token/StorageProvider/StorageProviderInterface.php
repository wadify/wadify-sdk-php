<?php

namespace Wadify\Token\StorageProvider;

use Wadify\Token\Token;

interface StorageProviderInterface
{
    /**
     * @return Token|null
     */
    public function get();

    /**
     * @param Token $token
     *
     * @return mixed
     */
    public function set(Token $token);
}