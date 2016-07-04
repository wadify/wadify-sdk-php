<?php

namespace Wadify\OAuth2\GrantType;

use Sainsburys\Guzzle\Oauth2\GrantType\GrantTypeBase;

class ApiKey extends GrantTypeBase
{
    const CONFIG_APIKEY = 'api_key';

    /**
     * @var string
     */
    protected $grantType = 'http://api.wadify.com/grants/api-key';

    /**
     * {@inheritdoc}
     */
    protected function getRequired()
    {
        return array_merge(parent::getRequired(), [self::CONFIG_APIKEY => '']);
    }
}