# wadify-sdk-php - Version 1

[![@wadifytech on Twitter](http://img.shields.io/badge/twitter-%40wadifytech-blue.svg?style=flat)](https://twitter.com/wadifytech)
[![Build Status](https://img.shields.io/travis/wadify/wadify-sdk-php.svg?style=flat)](https://travis-ci.org/wadify/wadify-sdk-php)

The **Wadify SDK for PHP** makes it easy for developers to access Wadify in their PHP code.

## Resources

* **README file** – For both getting started and in-depth SDK usage information
* **API DOCS** - For details about operations, parameters, and responses
* [Issues][sdk-issues] – Report issues, submit pull requests, and get involved
* [@wadifytech][sdk-twitter] – Follow us on Twitter

## Features

* Provides easy-to-use HTTP clients for all supported Wadify services and authentication protocols.
* Is built on Guzzle, and utilizes many of its features, including persistent connections, asynchronous requests, middlewares, etc.

## Getting started

1. **Sign up for Wadify** - Before you begin, you need to sign up for a Wadify account and retrieve your Wadify credentials
2. **Minimum requirements** - To run the SDK, your system will need to meet the minimum requirements, including having PHP >= 5.4 compiled with the cURL extension and cURL 7.16.2+ compiled with a TLS backend (e.g., NSS or OpenSSL).
3. **Install the SDK** – Using Composer is the recommended way to install the Wadify SDK for PHP. The SDK is available via Packagist under the wadify/wadify-sdk-php package.
4. **Using the SDK** – The best way to become familiar with how to use the SDK is to read the following secrtion. The Getting Started Guide will help you become familiar with the basic concepts.

## User guide

### Installation

#### Via composer cli

```bash
composer require wadify/wadify-sdk-php
```

#### Via composer json 
```json
{
...
	"require": {
		...,
		"wadify/wadify-sdk-php": "^1.0",
	}
}
```
and then

```bash
composer update
```

### Create a Wadify client

```php
<?php
// Require the Composer autoloader.
require __DIR__.'/vendor/autoload.php';

use Wadify\Client;

// Instantiate the client.
$apiKey = 'your-api-key';
$secret = 'your-secret-token';
$wadifyClient = new Client($apiKey, $secret, ['version' => 'latest']);
```

#### Options

* **version**: Block your desired api version. Ex. 0.0.1 or latest
* **sandbox**: true or false. If you want to use production or sandbox mode. Production by default

### Use the Wadify Client

#### Get user
```php
<?php
$user = $wadifyClient->getUser(); // array
```

#### Get transactions
```php
<?php
$transactions = $wadifyClient->getTransactions(); // array
```

#### Get transaction
```php
<?php
$id = 'your-trasaction-id';
$transaction = $wadifyClient->getTransaction($id); // array
```

#### Abort transaction
```php
<?php
$id = 'your-trasaction-id';
$transaction = $wadifyClient->abortTransaction($id); // array
```

#### Create transaction
```php
<?php
$data = [
    "amount" => 100,
    "subject" => "Transaction number one",
    "response_url" => "http://your.response.url/",
    "source_account" => "e76ad9ea-dbc1-11e5-a764-109add42947b",
    "destination_account" => [
        "name" => "Javier Rodriguez",
        "iban" => "ES1800491500042710151321"
    ],
    "fingerprint" => [
        "order" => "secret,amount,subject,response_url,source_account,destination_account.name,destination_account.iban",
        "hash" => "{hash}"
    ]
]
$transaction = $wadifyClient->createTransaction($data); // array
```

For further information about the data to send check the API Docs
