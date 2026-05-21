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

use Jobcloud\Kafka\Message\Decoder\DecoderInterface;
use Jobcloud\Kafka\Message\KafkaConsumerMessageInterface;
use Spotlibs\PhpLib\Exceptions\ParameterException;

/**
 * JsonSchemaDecoder
 *
 * Decodes Kafka consumer messages encoded with JSON Schema Confluent wire format.
 * Strips the wire format header (magic byte + schema ID), fetches the schema from
 * the registry, validates the JSON payload, and returns the decoded array.
 *
 * @category Library
 * @package  Libraries
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class JsonSchemaDecoder implements DecoderInterface
{
    /**
     * JsonSchemaDecoder constructor
     *
     * @param JsonSchemaRegistry $registry Schema registry client
     */
    public function __construct(private JsonSchemaRegistry $registry)
    {
    }

    /**
     * Decode a Kafka consumer message with JSON Schema wire format
     *
     * @param KafkaConsumerMessageInterface $consumerMessage Incoming Kafka message
     *
     * @return KafkaConsumerMessageInterface Decoded message with array body
     * @throws ParameterException
     */
    public function decode(KafkaConsumerMessageInterface $consumerMessage): KafkaConsumerMessageInterface
    {
        $body = $consumerMessage->getBody();

        if (!is_string($body) || strlen($body) <= 5) {
            return $consumerMessage;
        }

        if (ord($body[0]) !== 0) {
            return $consumerMessage;
        }

        $schemaId = unpack('N', substr($body, 1, 4))[1];
        $jsonPayload = substr($body, 5);

        // Fetch schema for validation
        $this->registry->getSchemaById($schemaId);

        $decoded = json_decode($jsonPayload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ParameterException('JSON Schema decode failed: invalid JSON payload');
        }

        return new KafkaDecodedMessage($consumerMessage, $decoded);
    }

    /**
     * Attempt to decode a wire-format message as JSON Schema
     *
     * Returns null if the payload after the header is not valid JSON,
     * indicating it may be Avro-encoded instead.
     *
     * @param string $body Raw message body bytes
     *
     * @return array|null Decoded array or null if not valid JSON
     */
    public static function tryDecode(string $body): ?array
    {
        if (strlen($body) <= 5 || ord($body[0]) !== 0) {
            return null;
        }

        $jsonPayload = substr($body, 5);
        $decoded = json_decode($jsonPayload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $decoded;
    }
}
