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

use Jobcloud\Kafka\Message\KafkaProducerMessageInterface;
use Mockery;
use PHPUnit\Framework\TestCase;
use Spotlibs\PhpLib\Exceptions\ParameterException;
use Spotlibs\PhpLib\Libraries\Kafka\JsonSchemaEncoder;
use Spotlibs\PhpLib\Libraries\Kafka\JsonSchemaRegistry;
use Spotlibs\PhpLib\Libraries\Kafka\KafkaJsonSchema;

/**
 * JsonSchemaEncoderTest
 *
 * Unit test for JsonSchemaEncoder
 *
 * @category Test
 * @package  Tests\Libraries\Kafka
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/
 * @covers   \Spotlibs\PhpLib\Libraries\Kafka\JsonSchemaEncoder
 * @covers   \Spotlibs\PhpLib\Libraries\Kafka\KafkaJsonSchema
 * @covers   \Spotlibs\PhpLib\Exceptions\ParameterException
 */
class JsonSchemaEncoderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function testEncodeBodyWithRegisteredSchema(): void
    {
        $registry = Mockery::mock(JsonSchemaRegistry::class);
        $registry->shouldReceive('register')->once()->andReturn(7);

        $bodySchema = new KafkaJsonSchema('topic-value', '{"type":"object"}');

        $msg = Mockery::mock(KafkaProducerMessageInterface::class);
        $msg->shouldReceive('getBody')->andReturn(['name' => 'test']);
        $msg->shouldReceive('withBody')->andReturnSelf();
        $msg->shouldReceive('getKey')->andReturn(null);

        $encoder = new JsonSchemaEncoder($registry, $bodySchema);
        $result = $encoder->encode($msg);

        $this->assertSame($msg, $result);
    }

    /** @test */
    public function testEncodeBodyUsesExistingSchemaId(): void
    {
        $registry = Mockery::mock(JsonSchemaRegistry::class);
        $registry->shouldReceive('register')->never();

        $bodySchema = new KafkaJsonSchema('topic-value', '{"type":"object"}');
        $bodySchema->setSchemaId(5);

        $msg = Mockery::mock(KafkaProducerMessageInterface::class);
        $msg->shouldReceive('getBody')->andReturn('{"name":"test"}');
        $msg->shouldReceive('withBody')->andReturnSelf();
        $msg->shouldReceive('getKey')->andReturn(null);

        $encoder = new JsonSchemaEncoder($registry, $bodySchema);
        $result = $encoder->encode($msg);

        $this->assertSame($msg, $result);
    }

    /** @test */
    public function testEncodeWithKeySchema(): void
    {
        $registry = Mockery::mock(JsonSchemaRegistry::class);
        $registry->shouldReceive('register')->twice()->andReturn(10);

        $bodySchema = new KafkaJsonSchema('topic-value', '{"type":"object"}');
        $keySchema = new KafkaJsonSchema('topic-key', '{"type":"string"}');

        $msg = Mockery::mock(KafkaProducerMessageInterface::class);
        $msg->shouldReceive('getBody')->andReturn(['id' => 1]);
        $msg->shouldReceive('withBody')->andReturnSelf();
        $msg->shouldReceive('getKey')->andReturn('my-key');
        $msg->shouldReceive('withKey')->andReturnSelf();

        $encoder = new JsonSchemaEncoder($registry, $bodySchema, $keySchema);
        $result = $encoder->encode($msg);

        $this->assertSame($msg, $result);
    }

    /** @test */
    public function testEncodeThrowsOnInvalidSchemaDefinition(): void
    {
        $this->expectException(ParameterException::class);

        $registry = Mockery::mock(JsonSchemaRegistry::class);
        $bodySchema = new KafkaJsonSchema('topic-value', 'not valid json schema{{{');
        $bodySchema->setSchemaId(1);

        $msg = Mockery::mock(KafkaProducerMessageInterface::class);
        $msg->shouldReceive('getBody')->andReturn(['name' => 'test']);

        $encoder = new JsonSchemaEncoder($registry, $bodySchema);
        $encoder->encode($msg);
    }

    /** @test */
    public function testEncodeThrowsOnInvalidPayload(): void
    {
        $this->expectException(\TypeError::class);

        $registry = Mockery::mock(JsonSchemaRegistry::class);
        $bodySchema = new KafkaJsonSchema('topic-value', '{"type":"object"}');
        $bodySchema->setSchemaId(1);

        $msg = Mockery::mock(KafkaProducerMessageInterface::class);
        $msg->shouldReceive('getBody')->andReturn(NAN);

        $encoder = new JsonSchemaEncoder($registry, $bodySchema);
        $encoder->encode($msg);
    }

    /** @test */
    public function testEncodeThrowsOnMissingRequiredField(): void
    {
        $this->expectException(ParameterException::class);

        $registry = Mockery::mock(JsonSchemaRegistry::class);
        $schema = '{"type":"object","required":["name"],"properties":{"name":{"type":"string"}}}';
        $bodySchema = new KafkaJsonSchema('topic-value', $schema);
        $bodySchema->setSchemaId(1);

        $msg = Mockery::mock(KafkaProducerMessageInterface::class);
        $msg->shouldReceive('getBody')->andReturn(['age' => 30]);

        $encoder = new JsonSchemaEncoder($registry, $bodySchema);
        $encoder->encode($msg);
    }

    /** @test */
    public function testEncodeThrowsOnTypeMismatch(): void
    {
        $this->expectException(ParameterException::class);

        $registry = Mockery::mock(JsonSchemaRegistry::class);
        $schema = '{"type":"object","properties":{"name":{"type":"string"}}}';
        $bodySchema = new KafkaJsonSchema('topic-value', $schema);
        $bodySchema->setSchemaId(1);

        $msg = Mockery::mock(KafkaProducerMessageInterface::class);
        $msg->shouldReceive('getBody')->andReturn(['name' => 123]);

        $encoder = new JsonSchemaEncoder($registry, $bodySchema);
        $encoder->encode($msg);
    }

    /** @test */
    public function testEncodePassesWithCorrectTypes(): void
    {
        $registry = Mockery::mock(JsonSchemaRegistry::class);
        $schema = '{"type":"object","required":["name","age"],"properties":{"name":{"type":"string"},"age":{"type":"integer"},"active":{"type":"boolean"},"score":{"type":"number"},"tags":{"type":"array"},"meta":{"type":"object"},"nothing":{"type":"null"}}}';
        $bodySchema = new KafkaJsonSchema('topic-value', $schema);
        $bodySchema->setSchemaId(1);

        $msg = Mockery::mock(KafkaProducerMessageInterface::class);
        $msg->shouldReceive('getBody')->andReturn([
            'name' => 'test',
            'age' => 25,
            'active' => true,
            'score' => 9.5,
            'tags' => ['a'],
            'meta' => (object)['k' => 'v'],
            'nothing' => null,
        ]);
        $msg->shouldReceive('withBody')->andReturnSelf();
        $msg->shouldReceive('getKey')->andReturn(null);

        $encoder = new JsonSchemaEncoder($registry, $bodySchema);
        $result = $encoder->encode($msg);

        $this->assertSame($msg, $result);
    }

    /** @test */
    public function testEncodeSkipsTypeValidationWhenNoTypeInSchema(): void
    {
        $registry = Mockery::mock(JsonSchemaRegistry::class);
        $schema = '{"type":"object","properties":{"name":{}}}';
        $bodySchema = new KafkaJsonSchema('topic-value', $schema);
        $bodySchema->setSchemaId(1);

        $msg = Mockery::mock(KafkaProducerMessageInterface::class);
        $msg->shouldReceive('getBody')->andReturn(['name' => 123]);
        $msg->shouldReceive('withBody')->andReturnSelf();
        $msg->shouldReceive('getKey')->andReturn(null);

        $encoder = new JsonSchemaEncoder($registry, $bodySchema);
        $result = $encoder->encode($msg);

        $this->assertSame($msg, $result);
    }

    /** @test */
    public function testEncodeWithMultiTypeAllowsNullable(): void
    {
        $registry = Mockery::mock(JsonSchemaRegistry::class);
        $schema = '{"type":"object","properties":{"name":{"type":["string","null"]}}}';
        $bodySchema = new KafkaJsonSchema('topic-value', $schema);
        $bodySchema->setSchemaId(1);

        $msg = Mockery::mock(KafkaProducerMessageInterface::class);
        $msg->shouldReceive('getBody')->andReturn(['name' => null]);
        $msg->shouldReceive('withBody')->andReturnSelf();
        $msg->shouldReceive('getKey')->andReturn(null);

        $encoder = new JsonSchemaEncoder($registry, $bodySchema);
        $result = $encoder->encode($msg);

        $this->assertSame($msg, $result);
    }

    /** @test */
    public function testEncodeWithKeySchemaAndNonStringKey(): void
    {
        $registry = Mockery::mock(JsonSchemaRegistry::class);
        $registry->shouldReceive('register')->twice()->andReturn(10);

        $bodySchema = new KafkaJsonSchema('topic-value', '{"type":"object"}');
        $keySchema = new KafkaJsonSchema('topic-key', '{"type":"object"}');

        $msg = Mockery::mock(KafkaProducerMessageInterface::class);
        $msg->shouldReceive('getBody')->andReturn(['id' => 1]);
        $msg->shouldReceive('withBody')->andReturnSelf();
        $msg->shouldReceive('getKey')->andReturn(['id' => 1]);
        $msg->shouldReceive('withKey')->andReturnSelf();

        $encoder = new JsonSchemaEncoder($registry, $bodySchema, $keySchema);
        $result = $encoder->encode($msg);

        $this->assertSame($msg, $result);
    }
}
