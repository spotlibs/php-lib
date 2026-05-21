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

use Jobcloud\Kafka\Message\KafkaConsumerMessageInterface;

/**
 * KafkaDecodedMessage
 *
 * Wraps a raw KafkaConsumerMessage and replaces its body with an already-decoded
 * value (e.g. an associative array from JSON). All other accessors are delegated
 * to the original message to preserve topic, partition, offset, and header info.
 *
 * @category Library
 * @package  Libraries
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class KafkaDecodedMessage implements KafkaConsumerMessageInterface
{
    /**
     * KafkaDecodedMessage constructor
     *
     * @param KafkaConsumerMessageInterface $original    Original raw Kafka message
     * @param mixed                         $decodedBody Pre-decoded message body
     */
    public function __construct(
        private KafkaConsumerMessageInterface $original,
        private mixed $decodedBody
    ) {
    }

    /**
     * Get the decoded message body
     *
     * @return mixed
     */
    public function getBody(): mixed
    {
        return $this->decodedBody;
    }

    /**
     * Get the message key
     *
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->original->getKey();
    }

    /**
     * Get the topic name
     *
     * @return string
     */
    public function getTopicName(): string
    {
        return $this->original->getTopicName();
    }

    /**
     * Get the partition number
     *
     * @return int
     */
    public function getPartition(): int
    {
        return $this->original->getPartition();
    }

    /**
     * Get the message headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->original->getHeaders();
    }

    /**
     * Get the message offset
     *
     * @return int
     */
    public function getOffset(): int
    {
        return $this->original->getOffset();
    }

    /**
     * Get the message timestamp
     *
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->original->getTimestamp();
    }
}
