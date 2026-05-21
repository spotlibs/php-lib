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
use Spotlibs\PhpLib\Libraries\Kafka\KafkaDecodedMessage;

/**
 * KafkaDecodedMessageTest
 *
 * Unit test for KafkaDecodedMessage
 *
 * @category Test
 * @package  Tests\Libraries\Kafka
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/
 * @covers   \Spotlibs\PhpLib\Libraries\Kafka\KafkaDecodedMessage
 */
class KafkaDecodedMessageTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function testGetBodyReturnsDecodedBody(): void
    {
        $original = Mockery::mock(KafkaConsumerMessageInterface::class);
        $decoded = ['key' => 'value'];

        $msg = new KafkaDecodedMessage($original, $decoded);
        $this->assertSame($decoded, $msg->getBody());
    }

    /** @test */
    public function testGetKeyDelegatesToOriginal(): void
    {
        $original = Mockery::mock(KafkaConsumerMessageInterface::class);
        $original->shouldReceive('getKey')->once()->andReturn('my-key');

        $msg = new KafkaDecodedMessage($original, []);
        $this->assertSame('my-key', $msg->getKey());
    }

    /** @test */
    public function testGetTopicNameDelegatesToOriginal(): void
    {
        $original = Mockery::mock(KafkaConsumerMessageInterface::class);
        $original->shouldReceive('getTopicName')->once()->andReturn('my-topic');

        $msg = new KafkaDecodedMessage($original, []);
        $this->assertSame('my-topic', $msg->getTopicName());
    }

    /** @test */
    public function testGetPartitionDelegatesToOriginal(): void
    {
        $original = Mockery::mock(KafkaConsumerMessageInterface::class);
        $original->shouldReceive('getPartition')->once()->andReturn(3);

        $msg = new KafkaDecodedMessage($original, []);
        $this->assertSame(3, $msg->getPartition());
    }

    /** @test */
    public function testGetHeadersDelegatesToOriginal(): void
    {
        $original = Mockery::mock(KafkaConsumerMessageInterface::class);
        $original->shouldReceive('getHeaders')->once()->andReturn(['h' => 'v']);

        $msg = new KafkaDecodedMessage($original, []);
        $this->assertSame(['h' => 'v'], $msg->getHeaders());
    }

    /** @test */
    public function testGetOffsetDelegatesToOriginal(): void
    {
        $original = Mockery::mock(KafkaConsumerMessageInterface::class);
        $original->shouldReceive('getOffset')->once()->andReturn(100);

        $msg = new KafkaDecodedMessage($original, []);
        $this->assertSame(100, $msg->getOffset());
    }

    /** @test */
    public function testGetTimestampDelegatesToOriginal(): void
    {
        $original = Mockery::mock(KafkaConsumerMessageInterface::class);
        $original->shouldReceive('getTimestamp')->once()->andReturn(1234567890);

        $msg = new KafkaDecodedMessage($original, []);
        $this->assertSame(1234567890, $msg->getTimestamp());
    }
}
