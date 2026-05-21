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

use GuzzleHttp\Client as GuzzleClient;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Spotlibs\PhpLib\Exceptions\ParameterException;
use Spotlibs\PhpLib\Libraries\Kafka\JsonSchemaRegistry;

/**
 * JsonSchemaRegistryTest
 *
 * Unit test for JsonSchemaRegistry
 *
 * @category Test
 * @package  Tests\Libraries\Kafka
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/
 * @covers   \Spotlibs\PhpLib\Libraries\Kafka\JsonSchemaRegistry
 * @covers   \Spotlibs\PhpLib\Exceptions\ParameterException
 */
class JsonSchemaRegistryTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function testRegisterSuccess(): void
    {
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->once()->andReturn(json_encode(['id' => 10]));

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->once()->andReturn($stream);

        $client = Mockery::mock(GuzzleClient::class);
        $client->shouldReceive('post')->once()->andReturn($response);

        $registry = new JsonSchemaRegistry($client);
        $result = $registry->register('topic-value', '{"type":"object"}');

        $this->assertSame(10, $result);
    }

    /** @test */
    public function testRegisterThrowsWhenNoId(): void
    {
        $this->expectException(ParameterException::class);

        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->once()->andReturn(json_encode([]));

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->once()->andReturn($stream);

        $client = Mockery::mock(GuzzleClient::class);
        $client->shouldReceive('post')->once()->andReturn($response);

        $registry = new JsonSchemaRegistry($client);
        $registry->register('topic-value', '{"type":"object"}');
    }

    /** @test */
    public function testRegisterThrowsOnHttpError(): void
    {
        $this->expectException(ParameterException::class);

        $client = Mockery::mock(GuzzleClient::class);
        $client->shouldReceive('post')->once()->andThrow(new \RuntimeException('connection failed'));

        $registry = new JsonSchemaRegistry($client);
        $registry->register('topic-value', '{"type":"object"}');
    }

    /** @test */
    public function testGetSchemaByIdSuccess(): void
    {
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->once()->andReturn(json_encode(['schema' => '{"type":"object"}']));

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->once()->andReturn($stream);

        $client = Mockery::mock(GuzzleClient::class);
        $client->shouldReceive('get')->once()->andReturn($response);

        $registry = new JsonSchemaRegistry($client);
        $result = $registry->getSchemaById(5);

        $this->assertSame('{"type":"object"}', $result);
    }

    /** @test */
    public function testGetSchemaByIdUsesCache(): void
    {
        // First call populates cache
        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->once()->andReturn(json_encode(['id' => 5]));

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->once()->andReturn($stream);

        $client = Mockery::mock(GuzzleClient::class);
        $client->shouldReceive('post')->once()->andReturn($response);
        $client->shouldReceive('get')->never();

        $registry = new JsonSchemaRegistry($client);
        $registry->register('topic-value', '{"type":"object"}');

        // Second call should use cache
        $result = $registry->getSchemaById(5);
        $this->assertSame('{"type":"object"}', $result);
    }

    /** @test */
    public function testGetSchemaByIdThrowsWhenNoSchema(): void
    {
        $this->expectException(ParameterException::class);

        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('getContents')->once()->andReturn(json_encode([]));

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->once()->andReturn($stream);

        $client = Mockery::mock(GuzzleClient::class);
        $client->shouldReceive('get')->once()->andReturn($response);

        $registry = new JsonSchemaRegistry($client);
        $registry->getSchemaById(99);
    }

    /** @test */
    public function testGetSchemaByIdThrowsOnHttpError(): void
    {
        $this->expectException(ParameterException::class);

        $client = Mockery::mock(GuzzleClient::class);
        $client->shouldReceive('get')->once()->andThrow(new \RuntimeException('timeout'));

        $registry = new JsonSchemaRegistry($client);
        $registry->getSchemaById(99);
    }
}
