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

namespace Tests\Exceptions;

use Spotlibs\PhpLib\Exceptions\AccessException;
use Spotlibs\PhpLib\Exceptions\DataNotFoundException;
use Spotlibs\PhpLib\Exceptions\StdException;
use Spotlibs\PhpLib\Exceptions\HeaderException;
use Spotlibs\PhpLib\Exceptions\InvalidRuleException;
use Spotlibs\PhpLib\Exceptions\ParameterException;
use Spotlibs\PhpLib\Exceptions\ThirdPartyServiceException;
use Spotlibs\PhpLib\Exceptions\UnsupportedException;
use Spotlibs\PhpLib\Exceptions\WaitingException;
use Spotlibs\PhpLib\Exceptions\RuntimeException;
use PHPUnit\Framework\TestCase;

final class StdExceptionTest extends TestCase
{
    public function testAccessException(): void
    {
        $this->expectException(AccessException::class);
        
        $e = StdException::create(
            StdException::ACCESS_EXCEPTION,
            null,
            ['x' => 'y']
        );
        $this->assertEquals(StdException::ACCESS_EXCEPTION, $e->getErrorCode());
        $this->assertEquals('Akses tidak diijinkan', $e->getErrorMessage());
        $this->assertEquals(['x' => 'y'], $e->getData());
        $this->assertEquals(403, $e->getHttpCode());
        throw $e;
    }
    public function testDataNotFoundException(): void
    {
        $this->expectException(DataNotFoundException::class);
        $e = StdException::create(
            StdException::NOTFOUND_EXCEPTION,
            null,
            ['x' => 'y']
        );
        $this->assertEquals(StdException::NOTFOUND_EXCEPTION, $e->getErrorCode());
        $this->assertEquals('Data tidak ditemukan', $e->getErrorMessage());
        $this->assertEquals(['x' => 'y'], $e->getData());
        $this->assertEquals(200, $e->getHttpCode());
        throw $e;
    }
    public function testHeaderException(): void
    {
        $this->expectException(HeaderException::class);
        $e = StdException::create(
            StdException::HEADER_EXCEPTION,
            null,
            ['x' => 'y']
        );
        $this->assertEquals(StdException::HEADER_EXCEPTION, $e->getErrorCode());
        $this->assertEquals('Header Request tidak valid', $e->getErrorMessage());
        $this->assertEquals(['x' => 'y'], $e->getData());
        $this->assertEquals(400, $e->getHttpCode());
        throw $e;
    }
    public function testInvalidRuleException(): void
    {
        $this->expectException(InvalidRuleException::class);
        $e = StdException::create(
            StdException::INVALIDRULE_EXCEPTION,
            null,
            ['x' => 'y']
        );
        $this->assertEquals(StdException::INVALIDRULE_EXCEPTION, $e->getErrorCode());
        $this->assertEquals('Validasi tidak terpenuhi', $e->getErrorMessage());
        $this->assertEquals(['x' => 'y'], $e->getData());
        $this->assertEquals(200, $e->getHttpCode());
        throw $e;
    }
    public function testParameterException(): void
    {
        $this->expectException(ParameterException::class);
        $e = StdException::create(
            StdException::PARAMETER_EXCEPTION,
            null,
            ['x' => 'y'],
            ['x' => 'y']
        );
        $this->assertEquals(StdException::PARAMETER_EXCEPTION, $e->getErrorCode());
        $this->assertEquals('Parameter tidak sesuai', $e->getErrorMessage());
        $this->assertEquals(['x' => 'y'], $e->getData());
        if ($e instanceof ParameterException) {
            $this->assertEquals(['x' => 'y'], $e->getValidationErrors());
        }
        $this->assertEquals(200, $e->getHttpCode());
        throw $e;
    }
    public function testRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);
        $e = StdException::create(
            StdException::RUNTIME_EXCEPTION, 
            null,
            ['x' => 'y']
        );
        $this->assertEquals(StdException::RUNTIME_EXCEPTION, $e->getErrorCode());
        $this->assertEquals('Runtime error happens', $e->getErrorMessage());
        $this->assertEquals(['x' => 'y'], $e->getData());
        $this->assertEquals(200, $e->getHttpCode());
        throw $e;
    }
    public function testThirdPartyServiceException(): void
    {
        $this->expectException(ThirdPartyServiceException::class);
        $e = StdException::create(
            StdException::THIRDPARTY_EXCEPTION,
            'google not responding',
            ['x' => 'y']
        );
        $this->assertEquals(StdException::THIRDPARTY_EXCEPTION, $e->getErrorCode());
        $this->assertEquals('google not responding', $e->getErrorMessage());
        $this->assertEquals(['x' => 'y'], $e->getData());
        $this->assertEquals(200, $e->getHttpCode());
        throw $e;
    }
    public function testUnsupportedException(): void
    {
        $this->expectException(UnsupportedException::class);
        $e = StdException::create(
            StdException::UNSUPPORTED_EXCEPTION,
            null,
            ['x' => 'y']
        );
        $this->assertEquals(StdException::UNSUPPORTED_EXCEPTION, $e->getErrorCode());
        $this->assertEquals('Tidak disupport', $e->getErrorMessage());
        $this->assertEquals(['x' => 'y'], $e->getData());
        $this->assertEquals(200, $e->getHttpCode());
        throw $e;
    }
    public function testWaitingException(): void
    {
        $this->expectException(WaitingException::class);
        $e = StdException::create(
            StdException::WAITING_EXCEPTION,
            null,
            ['x' => 'y']
        );
        $this->assertEquals(StdException::WAITING_EXCEPTION, $e->getErrorCode());
        $this->assertEquals('Masih proses harap tunggu', $e->getErrorMessage());
        $this->assertEquals(['x' => 'y'], $e->getData());
        $this->assertEquals(200, $e->getHttpCode());
        throw $e;
    }
}
