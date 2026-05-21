<?php

/**
 * PHP version 8.0.30
 *
 * @category Application
 * @package  Tests\Libraries\Kafka
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.1
 * @link     https://github.com/
 */

declare(strict_types=1);

namespace Tests\Libraries\Kafka;

use Jobcloud\Kafka\Message\KafkaConsumerMessageInterface;
use Mockery;
use PHPUnit\Framework\TestCase;
use Spotlibs\PhpLib\Exceptions\ParameterException;
use Spotlibs\PhpLib\Libraries\Kafka\JsonSchemaDecoder;
use Spotlibs\PhpLib\Libraries\Kafka\JsonSchemaRegistry;
use Spotlibs\PhpLib\Libraries\Kafka\KafkaDecodedMessage;

/**
 * JsonSchemaDecoderTest
 *
 * Unit test for JsonSchemaDecoder
 *
 * @category Test
 * @package  Tests\Libraries\Kafka
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/
 * @covers   \Spotlibs\PhpLib\Libraries\Kafka\JsonSchemaDecoder
 * @covers   \Spotlibs\PhpLib\Libraries\Kafka\KafkaDecodedMessage
 * @covers   \Spotlibs\PhpLib\Exceptions\ParameterException
 */
class JsonSchemaDecoderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function testDecodeReturnsOriginalWhenBodyNotString(): void
    {
        $registry = Mockery::mock(JsonSchemaRegistry::class);
        $msg = Mockery::mock(KafkaConsumerMessageInterface::class);
        $msg->shouldReceive('getBody')->once()->andReturn(null);

        $decoder = new JsonSchemaDecoder($registry);
        $result = $decoder->decode($msg);

        $this->assertSame($msg, $result);
    }

    /** @test */
    public function testDecodeReturnsOriginalWhenBodyTooShort(): void
    {
        $registry = Mockery::mock(JsonSchemaRegistry::class);
        $msg = Mockery::mock(KafkaConsumerMessageInterface::class);
        $msg->shouldReceive('getBody')->once()->andReturn('abc');

        $decoder = new JsonSchemaDecoder($registry);
        $result = $decoder->decode($msg);

        $this->assertSame($msg, $result);
    }

    /** @test */
    public function testDecodeReturnsOriginalWhenNoMagicByte(): void
    {
        $registry = Mockery::mock(JsonSchemaRegistry::class);
        $body = 'X' . pack('N', 1) . '{"a":1}';
        $msg = Mockery::mock(KafkaConsumerMessageInterface::class);
        $msg->shouldReceive('getBody')->once()->andReturn($body);

        $decoder = new JsonSchemaDecoder($registry);
        $result = $decoder->decode($msg);

        $this->assertSame($msg, $result);
    }

    /** @test */
    public function testDecodeSuccess(): void
    {
        $registry = Mockery::mock(JsonSchemaRegistry::class);
        $registry->shouldReceive('getSchemaById')->once()->with(1)->andReturn('{}');

        $payload = json_encode(['name' => 'test']);
        $body = "\x00" . pack('N', 1) . $payload;

        $msg = Mockery::mock(KafkaConsumerMessageInterface::class);
        $msg->shouldReceive('getBody')->once()->andReturn($body);

        $decoder = new JsonSchemaDecoder($registry);
        $result = $decoder->decode($msg);

        $this->assertInstanceOf(KafkaDecodedMessage::class, $result);
        $this->assertSame(['name' => 'test'], $result->getBody());
    }

    /** @test */
    public function testDecodeThrowsOnInvalidJson(): void
    {
        $this->expectException(ParameterException::class);

        $registry = Mockery::mock(JsonSchemaRegistry::class);
        $registry->shouldReceive('getSchemaById')->once()->andReturn('{}');

        $body = "\x00" . pack('N', 1) . '{invalid json';

        $msg = Mockery::mock(KafkaConsumerMessageInterface::class);
        $msg->shouldReceive('getBody')->once()->andReturn($body);

        $decoder = new JsonSchemaDecoder($registry);
        $decoder->decode($msg);
    }

    /** @test */
    public function testTryDecodeReturnsNullWhenTooShort(): void
    {
        $result = JsonSchemaDecoder::tryDecode('abc');
        $this->assertNull($result);
    }

    /** @test */
    public function testTryDecodeReturnsNullWhenNoMagicByte(): void
    {
        $body = 'X' . pack('N', 1) . '{"a":1}';
        $result = JsonSchemaDecoder::tryDecode($body);
        $this->assertNull($result);
    }

    /** @test */
    public function testTryDecodeReturnsNullOnInvalidJson(): void
    {
        $body = "\x00" . pack('N', 1) . '{not json';
        $result = JsonSchemaDecoder::tryDecode($body);
        $this->assertNull($result);
    }

    /** @test */
    public function testTryDecodeReturnsArrayOnValidJson(): void
    {
        $body = "\x00" . pack('N', 1) . json_encode(['foo' => 'bar']);
        $result = JsonSchemaDecoder::tryDecode($body);
        $this->assertSame(['foo' => 'bar'], $result);
    }
}
