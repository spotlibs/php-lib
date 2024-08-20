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

namespace Tests\Exceptions;

use Brispot\PhpLib\Exceptions\AccessException;
use Brispot\PhpLib\Exceptions\DataNotFoundException;
use Brispot\PhpLib\Exceptions\ExceptionFactory;
use Brispot\PhpLib\Exceptions\HeaderException;
use Brispot\PhpLib\Exceptions\InvalidRuleException;
use Brispot\PhpLib\Exceptions\ParameterException;
use Brispot\PhpLib\Exceptions\ThirdPartyServiceException;
use Brispot\PhpLib\Exceptions\UnsupportedException;
use Brispot\PhpLib\Exceptions\WaitingException;
use Brispot\PhpLib\Exceptions\RuntimeException;
use Exception;
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
