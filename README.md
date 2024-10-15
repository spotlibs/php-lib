# spotlibs-php-lib

[![Build Status](https://github.com/spotlibs/php-lib/actions/workflows/php.yml/badge.svg)](https://github.com/spotlibs/php-lib/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/spotlibs/php-lib)](https://packagist.org/packages/spotlibs/php-lib)
[![Latest Stable Version](https://img.shields.io/packagist/v/spotlibs/php-lib)](https://packagist.org/packages/laravel/lumen-framework)
[![License](https://img.shields.io/packagist/l/spotlibs/php-lib)](https://packagist.org/packages/spotlibs/php-lib)

## Description
This library is used to support easier development of Spotlibs Microservice Project.

This library provides rest client for communication between microservice.
This library provides kafka client both producer and consumer with support avro serde.

## Install

    composer require spotlibs/php-lib

## Usage
- Exception
```php
<?php

declare(strict_types=1);

use Spotlibs\PhpLib\Exceptions\StdException;
use Spotlibs\PhpLib\Exceptions\AccessException;

class ExceptionUsage
{
    public function ThrowingException()
    {
        throw new AccessException('You shall not pass!');
    }

    // you can also throw any exception by static function of StdException
    public function ThrowingStandardException()
    {
        throw StdException::create(
            StdException::HEADER_EXCEPTION,
            'Invalid headers to access this resource',
        );
    }
}
```
