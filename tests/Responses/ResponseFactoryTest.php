<?php

declare(strict_types=1);

/**
 * PHP version 8
 *
 * @category Library
 * @package  Exceptions
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.2
 * @link     https://github.com/brispot
 */

namespace Tests\Responses;

use Brispot\PhpLib\Responses\ResponseFactory;
use Brispot\PhpLib\Exceptions\ExceptionFactory;
use PHPUnit\Framework\TestCase;

final class ResponseFactoryTest extends TestCase
{
    public function testSuccessResponse(): void
    {
        $r = ResponseFactory::success('Success', ['x' => 'y']);
        $this->assertEquals(200, $r->getStatusCode());
        $responseBody = json_decode($r->getContent(), true);
        $this->assertEquals('00', $responseBody['responseCode']);
        $this->assertEquals('Success', $responseBody['responseDesc']);
    }
    public function testFailureResponse(): void
    {
        $r = ResponseFactory::failure(ExceptionFactory::create(ExceptionFactory::INVALIDRULE_EXCEPTION));
        $this->assertEquals(200, $r->getStatusCode());
        $responseBody = json_decode($r->getContent(), true);
        $this->assertEquals(ExceptionFactory::INVALIDRULE_EXCEPTION, $responseBody['responseCode']);
        $this->assertEquals('Validasi tidak terpenuhi', $responseBody['responseDesc']);
    }
    public function testFailureResponse2(): void
    {
        $r = ResponseFactory::failure(ExceptionFactory::create(ExceptionFactory::INVALIDRULE_EXCEPTION, null, ['x' => 'y']));
        $this->assertEquals(200, $r->getStatusCode());
        $responseBody = json_decode($r->getContent(), true);
        $this->assertEquals(ExceptionFactory::INVALIDRULE_EXCEPTION, $responseBody['responseCode']);
        $this->assertEquals('Validasi tidak terpenuhi', $responseBody['responseDesc']);
    }
}