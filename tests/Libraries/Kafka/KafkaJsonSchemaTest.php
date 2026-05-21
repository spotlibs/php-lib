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

use PHPUnit\Framework\TestCase;
use Spotlibs\PhpLib\Libraries\Kafka\KafkaJsonSchema;

/**
 * KafkaJsonSchemaTest
 *
 * Unit test for KafkaJsonSchema
 *
 * @category Test
 * @package  Tests\Libraries\Kafka
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/
 * @covers   \Spotlibs\PhpLib\Libraries\Kafka\KafkaJsonSchema
 */
class KafkaJsonSchemaTest extends TestCase
{
    /** @test */
    public function testGetSubject(): void
    {
        $schema = new KafkaJsonSchema('topic-value', '{"type":"object"}');
        $this->assertSame('topic-value', $schema->getSubject());
    }

    /** @test */
    public function testGetDefinition(): void
    {
        $schema = new KafkaJsonSchema('topic-value', '{"type":"object"}');
        $this->assertSame('{"type":"object"}', $schema->getDefinition());
    }

    /** @test */
    public function testGetSchemaIdReturnsNullByDefault(): void
    {
        $schema = new KafkaJsonSchema('topic-value', '{"type":"object"}');
        $this->assertNull($schema->getSchemaId());
    }

    /** @test */
    public function testSetAndGetSchemaId(): void
    {
        $schema = new KafkaJsonSchema('topic-value', '{"type":"object"}');
        $schema->setSchemaId(42);
        $this->assertSame(42, $schema->getSchemaId());
    }
}
