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
use Spotlibs\PhpLib\Exceptions\ExceptionFactory;
use Spotlibs\PhpLib\Exceptions\HeaderException;
use Spotlibs\PhpLib\Exceptions\InvalidRuleException;
use Spotlibs\PhpLib\Exceptions\ParameterException;
use Spotlibs\PhpLib\Exceptions\ThirdPartyServiceException;
use Spotlibs\PhpLib\Exceptions\UnsupportedException;
use Spotlibs\PhpLib\Exceptions\WaitingException;
use Spotlibs\PhpLib\Exceptions\RuntimeException;
use PHPUnit\Framework\TestCase;

final class ExceptionFactoryTest extends TestCase
{
    public function testAccessException(): void
    {
        $this->expectException(AccessException::class);
        
        $e = ExceptionFactory::create(
            ExceptionFactory::ACCESS_EXCEPTION,
            null,
            ['x' => 'y']
        );
        $this->assertEquals(ExceptionFactory::ACCESS_EXCEPTION, $e->getErrorCode());
        $this->assertEquals('Akses tidak diijinkan', $e->getErrorMessage());
        $this->assertEquals(['x' => 'y'], $e->getData());
        $this->assertEquals(403, $e->getHttpCode());
        throw $e;
    }
    public function testDataNotFoundException(): void
    {
        $this->expectException(DataNotFoundException::class);
        $e = ExceptionFactory::create(
            ExceptionFactory::NOTFOUND_EXCEPTION,
            null,
            ['x' => 'y']
        );
        $this->assertEquals(ExceptionFactory::NOTFOUND_EXCEPTION, $e->getErrorCode());
        $this->assertEquals('Data tidak ditemukan', $e->getErrorMessage());
        $this->assertEquals(['x' => 'y'], $e->getData());
        $this->assertEquals(200, $e->getHttpCode());
        throw $e;
    }
    public function testHeaderException(): void
    {
        $this->expectException(HeaderException::class);
        $e = ExceptionFactory::create(
            ExceptionFactory::HEADER_EXCEPTION,
            null,
            ['x' => 'y']
        );
        $this->assertEquals(ExceptionFactory::HEADER_EXCEPTION, $e->getErrorCode());
        $this->assertEquals('Header Request tidak valid', $e->getErrorMessage());
        $this->assertEquals(['x' => 'y'], $e->getData());
        $this->assertEquals(400, $e->getHttpCode());
        throw $e;
    }
    public function testInvalidRuleException(): void
    {
        $this->expectException(InvalidRuleException::class);
        $e = ExceptionFactory::create(
            ExceptionFactory::INVALIDRULE_EXCEPTION,
            null,
            ['x' => 'y']
        );
        $this->assertEquals(ExceptionFactory::INVALIDRULE_EXCEPTION, $e->getErrorCode());
        $this->assertEquals('Validasi tidak terpenuhi', $e->getErrorMessage());
        $this->assertEquals(['x' => 'y'], $e->getData());
        $this->assertEquals(200, $e->getHttpCode());
        throw $e;
    }
    public function testParameterException(): void
    {
        $this->expectException(ParameterException::class);
        $e = ExceptionFactory::create(
            ExceptionFactory::PARAMETER_EXCEPTION,
            null,
            ['x' => 'y']
        );
        $this->assertEquals(ExceptionFactory::PARAMETER_EXCEPTION, $e->getErrorCode());
        $this->assertEquals('Parameter tidak sesuai', $e->getErrorMessage());
        $this->assertEquals(['x' => 'y'], $e->getData());
        $this->assertEquals(200, $e->getHttpCode());
        throw $e;
    }
    public function testRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);
        $e = ExceptionFactory::create(
            ExceptionFactory::RUNTIME_EXCEPTION, 
            null,
            ['x' => 'y']
        );
        $this->assertEquals(ExceptionFactory::RUNTIME_EXCEPTION, $e->getErrorCode());
        $this->assertEquals('Runtime error happens', $e->getErrorMessage());
        $this->assertEquals(['x' => 'y'], $e->getData());
        $this->assertEquals(200, $e->getHttpCode());
        throw $e;
    }
    public function testThirdPartyServiceException(): void
    {
        $this->expectException(ThirdPartyServiceException::class);
        $e = ExceptionFactory::create(
            ExceptionFactory::THIRDPARTY_EXCEPTION,
            'google not responding',
            ['x' => 'y']
        );
        $this->assertEquals(ExceptionFactory::THIRDPARTY_EXCEPTION, $e->getErrorCode());
        $this->assertEquals('google not responding', $e->getErrorMessage());
        $this->assertEquals(['x' => 'y'], $e->getData());
        $this->assertEquals(200, $e->getHttpCode());
        throw $e;
    }
    public function testUnsupportedException(): void
    {
        $this->expectException(UnsupportedException::class);
        $e = ExceptionFactory::create(
            ExceptionFactory::UNSUPPORTED_EXCEPTION,
            null,
            ['x' => 'y']
        );
        $this->assertEquals(ExceptionFactory::UNSUPPORTED_EXCEPTION, $e->getErrorCode());
        $this->assertEquals('Tidak disupport', $e->getErrorMessage());
        $this->assertEquals(['x' => 'y'], $e->getData());
        $this->assertEquals(200, $e->getHttpCode());
        throw $e;
    }
    public function testWaitingException(): void
    {
        $this->expectException(WaitingException::class);
        $e = ExceptionFactory::create(
            ExceptionFactory::WAITING_EXCEPTION,
            null,
            ['x' => 'y']
        );
        $this->assertEquals(ExceptionFactory::WAITING_EXCEPTION, $e->getErrorCode());
        $this->assertEquals('Masih proses harap tunggu', $e->getErrorMessage());
        $this->assertEquals(['x' => 'y'], $e->getData());
        $this->assertEquals(200, $e->getHttpCode());
        throw $e;
    }
}
