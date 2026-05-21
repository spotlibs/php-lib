<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Libraries
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.4.0
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Libraries\Kafka;

/**
 * KafkaJsonSchema
 *
 * Holds a JSON Schema definition with its subject name and resolved schema ID
 * from the Confluent Schema Registry.
 *
 * @category Library
 * @package  Libraries
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class KafkaJsonSchema
{
    /**
     * Schema ID from registry (null until registered/fetched)
     *
     * @var int|null
     */
    private ?int $schemaId = null;

    /**
     * KafkaJsonSchema constructor
     *
     * @param string $subject    Subject name in the registry (e.g. "topic-value")
     * @param string $definition Raw JSON Schema definition string
     */
    public function __construct(
        private string $subject,
        private string $definition
    ) {
    }

    /**
     * Get the subject name
     *
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * Get the JSON Schema definition string
     *
     * @return string
     */
    public function getDefinition(): string
    {
        return $this->definition;
    }

    /**
     * Get the resolved schema ID
     *
     * @return int|null
     */
    public function getSchemaId(): ?int
    {
        return $this->schemaId;
    }

    /**
     * Set the resolved schema ID
     *
     * @param int $schemaId Schema ID from registry
     *
     * @return void
     */
    public function setSchemaId(int $schemaId): void
    {
        $this->schemaId = $schemaId;
    }
}
