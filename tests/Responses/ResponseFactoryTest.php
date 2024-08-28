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
 * @link     https://github.com/spotlibs
 */

namespace Tests\Responses;

use Spotlibs\PhpLib\Responses\StdResponse;
use Spotlibs\PhpLib\Exceptions\StdException;
use PHPUnit\Framework\TestCase;

final class StdResponseTest extends TestCase
{
    public function testSuccessResponse(): void
    {
        $r = StdResponse::success('Success', ['x' => 'y']);
        $this->assertEquals(200, $r->getStatusCode());
        $responseBody = json_decode($r->getContent(), true);
        $this->assertEquals('00', $responseBody['responseCode']);
        $this->assertEquals('Success', $responseBody['responseDesc']);
    }
    public function testFailureResponse(): void
    {
        $r = StdResponse::error(StdException::create(StdException::INVALIDRULE_EXCEPTION));
        $this->assertEquals(200, $r->getStatusCode());
        $responseBody = json_decode($r->getContent(), true);
        $this->assertEquals(StdException::INVALIDRULE_EXCEPTION, $responseBody['responseCode']);
        $this->assertEquals('Validasi tidak terpenuhi', $responseBody['responseDesc']);
    }
    public function testFailureResponse2(): void
    {
        $r = StdResponse::error(StdException::create(StdException::INVALIDRULE_EXCEPTION, null, ['x' => 'y']));
        $this->assertEquals(200, $r->getStatusCode());
        $responseBody = json_decode($r->getContent(), true);
        $this->assertEquals(StdException::INVALIDRULE_EXCEPTION, $responseBody['responseCode']);
        $this->assertEquals('Validasi tidak terpenuhi', $responseBody['responseDesc']);
    }
}