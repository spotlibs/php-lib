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
- Response

Standardized response body for a consistent response body structure
```php
<?php

declare(strict_types=1);

use Spotlibs\PhpLib\Responses\StdResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Models\Post;

class DailyNews extends Controller
{
    public function index(Request $request): Response
    {
        Post::create([
            'headline' => 'Top 10 Countries with Best Cyber Security, Finland Ranked First',
            'content' => 'Here are the top 10 countries with the best cyber securities based on the data published by Mix Mode and 2024 Global Security Index:'
        ]);
        return StdResponse::success('Daily news posted.');
    }
}
```
- DTO
```php
<?php

declare(strict_types=1);

use Spotlibs\PhpLib\Dtos\TraitDtos;

class Person
{
    use TraitDtos;

    public string $name;
    public int $age;
    public array $hobbies;
}

class Introduction
{
    protected Person $person;

    public function __construct()
    {
        // pass associative array to construct DTO
        $this->person = new Person([
            'name' => 'John',
            'age' => 30,
            'hobbies' => ['fishing', 'sleeping', 'coding']
        ]);
    }

    public function index(): void
    {
        echo "Hi, my name is " . $this->person->name . ". I am " . $this->person->age . " year(s) old" . PHP_EOL;
    }
}
```
- Context

Implement a context as an object containing several key:value from the begining to the end of request lifecycle. To use context, add this service provider to your bootstrap/app.php for Lumen or config/app.php for Laravel
> $app->register(\Spotlibs\PhpLib\Providers\ContextServiceProvider::class);

- Activity Middleware

Request and response middleware to initiate context and writing activity log. To use this middleware, add this line to your app middleware setting
```php
$app->middleware([
    //...
    \Spotlibs\PhpLib\Middlewares\ActivityMonitor::class,
    //...
]);
```
- Logger
```php
<?php

declare(strict_types=1);

use Spotlibs\PhpLib\Logs\Log;

class HoldingBeer
{
    public function HoldMyBeer()
    {
        Log::activity()->info(['timestamp' => '2006-01-02 15:04:05', 'status' => 'accomplished']);
        Log::worker()->info(['timestamp' => '2006-01-02 15:04:05', 'job' => 'holding bro\'s beer']);
        Log::runtime()->info(['timestamp' => '2006-01-02 15:04:05', 'error' => 'the beer likely to spilled']);
        Log::runtime()->warning(['timestamp' => '2006-01-02 15:04:05', 'error' => 'the beer gonna be spilled']);
        Log::runtime()->error(['timestamp' => '2006-01-02 15:04:05', 'error' => 'the beer got spilled']);
    }
}
```
- Queue

add this service provider to your bootstrap/app.php for Lumen or config/app.php for Laravel
```php
$app->register(\Spotlibs\PhpLib\Providers\QueueEventServiceProvider::class);
```

and here is how to use Queue
```php
<?php

declare(strict_types=1);

use Spotlibs\PhpLib\Facades\Queue;
use App\Jobs\SendMoney;
use App\Models\Employee;

class Payday
{
    public function index(Request $request): void
    {
        $employees = Employee::get();
        foreach ($employees as $employee) {
            Queue::pushOn('payday', new SendMoney());
        }
    }
}
```