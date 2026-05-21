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

use FlixTech\AvroSerializer\Objects\RecordSerializer;
use Jobcloud\Kafka\Message\Decoder\AvroDecoder;
use Jobcloud\Kafka\Message\KafkaConsumerMessageInterface;
use Jobcloud\Kafka\Message\Registry\AvroSchemaRegistryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;
use Spotlibs\PhpLib\Libraries\Kafka\JsonSchemaDecoder;
use Spotlibs\PhpLib\Libraries\Kafka\KafkaAutoDecoder;
use Spotlibs\PhpLib\Libraries\Kafka\KafkaDecodedMessage;

/**
 * KafkaAutoDecoderTest
 *
 * Unit test for KafkaAutoDecoder
 *
 * @category Test
 * @package  Tests\Libraries\Kafka
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/
 * @covers   \Spotlibs\PhpLib\Libraries\Kafka\KafkaAutoDecoder
 * @covers   \Spotlibs\PhpLib\Libraries\Kafka\JsonSchemaDecoder
 * @covers   \Spotlibs\PhpLib\Libraries\Kafka\KafkaDecodedMessage
 */
class KafkaAutoDecoderTest extends TestCase
{
    private AvroDecoder $avroDecoder;

    protected function setUp(): void
    {
        parent::setUp();
        $registry = Mockery::mock(AvroSchemaRegistryInterface::class);
        $recordSerializer = Mockery::mock(RecordSerializer::class);
        $this->avroDecoder = new AvroDecoder($registry, $recordSerializer);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function testDecodeReturnsOriginalWhenBodyNotString(): void
    {
        $msg = Mockery::mock(KafkaConsumerMessageInterface::class);
        $msg->shouldReceive('getBody')->andReturn(null);

        $decoder = new KafkaAutoDecoder($this->avroDecoder);
        $result = $decoder->decode($msg);

        $this->assertSame($msg, $result);
    }

    /** @test */
    public function testDecodeReturnsOriginalWhenBodyTooShort(): void
    {
        $msg = Mockery::mock(KafkaConsumerMessageInterface::class);
        $msg->shouldReceive('getBody')->andReturn('short');

        $decoder = new KafkaAutoDecoder($this->avroDecoder);
        $result = $decoder->decode($msg);

        $this->assertSame($msg, $result);
    }

    /** @test */
    public function testDecodeWireFormatJsonSchemaWithDecoder(): void
    {
        $payload = json_encode(['key' => 'value']);
        $body = "\x00" . pack('N', 1) . $payload;

        $msg = Mockery::mock(KafkaConsumerMessageInterface::class);
        $msg->shouldReceive('getBody')->andReturn($body);

        $decodedMsg = Mockery::mock(KafkaConsumerMessageInterface::class);

        $jsonDecoder = Mockery::mock(JsonSchemaDecoder::class);
        $jsonDecoder->shouldReceive('decode')->once()->andReturn($decodedMsg);

        $decoder = new KafkaAutoDecoder($this->avroDecoder, $jsonDecoder);
        $result = $decoder->decode($msg);

        $this->assertSame($decodedMsg, $result);
    }

    /** @test */
    public function testDecodeWireFormatJsonSchemaFallsBackOnDecoderException(): void
    {
        $payload = json_encode(['key' => 'value']);
        $body = "\x00" . pack('N', 1) . $payload;

        $msg = Mockery::mock(KafkaConsumerMessageInterface::class);
        $msg->shouldReceive('getBody')->andReturn($body);

        $jsonDecoder = Mockery::mock(JsonSchemaDecoder::class);
        $jsonDecoder->shouldReceive('decode')->once()->andThrow(new \RuntimeException('fail'));

        $decoder = new KafkaAutoDecoder($this->avroDecoder, $jsonDecoder);
        $result = $decoder->decode($msg);

        $this->assertInstanceOf(KafkaDecodedMessage::class, $result);
        $this->assertSame(['key' => 'value'], $result->getBody());
    }

    /** @test */
    public function testDecodeWireFormatJsonSchemaWithoutDecoder(): void
    {
        $payload = json_encode(['key' => 'value']);
        $body = "\x00" . pack('N', 1) . $payload;

        $msg = Mockery::mock(KafkaConsumerMessageInterface::class);
        $msg->shouldReceive('getBody')->andReturn($body);

        $decoder = new KafkaAutoDecoder($this->avroDecoder);
        $result = $decoder->decode($msg);

        $this->assertInstanceOf(KafkaDecodedMessage::class, $result);
        $this->assertSame(['key' => 'value'], $result->getBody());
    }

    /** @test */
    public function testDecodeWireFormatFallsBackToAvro(): void
    {
        // Binary payload that is NOT valid JSON after header
        $body = "\x00" . pack('N', 1) . "\x02\x06foo";

        $msg = Mockery::mock(KafkaConsumerMessageInterface::class);
        $msg->shouldReceive('getBody')->andReturn($body);
        $msg->shouldReceive('getTopicName')->andReturn('test-topic');
        $msg->shouldReceive('getPartition')->andReturn(0);
        $msg->shouldReceive('getOffset')->andReturn(0);
        $msg->shouldReceive('getTimestamp')->andReturn(0);
        $msg->shouldReceive('getKey')->andReturn(null);
        $msg->shouldReceive('getHeaders')->andReturn([]);

        $registry = Mockery::mock(AvroSchemaRegistryInterface::class);
        $registry->shouldReceive('hasBodySchemaForTopic')->andReturn(false);
        $registry->shouldReceive('hasKeySchemaForTopic')->andReturn(false);

        $recordSerializer = Mockery::mock(RecordSerializer::class);
        $avroDecoder = new AvroDecoder($registry, $recordSerializer);

        $decoder = new KafkaAutoDecoder($avroDecoder);
        $result = $decoder->decode($msg);

        // AvroDecoder returns a new KafkaConsumerMessage (not the same instance)
        $this->assertInstanceOf(KafkaConsumerMessageInterface::class, $result);
    }

    /** @test */
    public function testDecodeWireFormatReturnsOriginalWhenAvroFails(): void
    {
        $body = "\x00" . pack('N', 1) . "\x02\x06foo";

        $msg = Mockery::mock(KafkaConsumerMessageInterface::class);
        $msg->shouldReceive('getBody')->andReturn($body);
        $msg->shouldReceive('getTopicName')->andReturn('test-topic');

        $registry = Mockery::mock(AvroSchemaRegistryInterface::class);
        $registry->shouldReceive('hasBodySchemaForTopic')->andThrow(new \RuntimeException('avro fail'));

        $recordSerializer = Mockery::mock(RecordSerializer::class);
        $avroDecoder = new AvroDecoder($registry, $recordSerializer);

        $decoder = new KafkaAutoDecoder($avroDecoder);
        $result = $decoder->decode($msg);

        $this->assertSame($msg, $result);
    }

    /** @test */
    public function testDecodePlainJson(): void
    {
        $body = json_encode(['hello' => 'world']);

        $msg = Mockery::mock(KafkaConsumerMessageInterface::class);
        $msg->shouldReceive('getBody')->andReturn($body);

        $decoder = new KafkaAutoDecoder($this->avroDecoder);
        $result = $decoder->decode($msg);

        $this->assertInstanceOf(KafkaDecodedMessage::class, $result);
        $this->assertSame(['hello' => 'world'], $result->getBody());
    }

    /** @test */
    public function testDecodePlainJsonArray(): void
    {
        $body = json_encode([1, 2, 3]);

        $msg = Mockery::mock(KafkaConsumerMessageInterface::class);
        $msg->shouldReceive('getBody')->andReturn($body);

        $decoder = new KafkaAutoDecoder($this->avroDecoder);
        $result = $decoder->decode($msg);

        $this->assertInstanceOf(KafkaDecodedMessage::class, $result);
        $this->assertSame([1, 2, 3], $result->getBody());
    }

    /** @test */
    public function testDecodeReturnsOriginalForNonJsonPlainText(): void
    {
        $body = 'this is just plain text that is longer than 5 chars';

        $msg = Mockery::mock(KafkaConsumerMessageInterface::class);
        $msg->shouldReceive('getBody')->andReturn($body);

        $decoder = new KafkaAutoDecoder($this->avroDecoder);
        $result = $decoder->decode($msg);

        $this->assertSame($msg, $result);
    }
}
