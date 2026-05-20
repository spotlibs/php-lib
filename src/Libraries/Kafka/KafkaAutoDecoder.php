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

use Jobcloud\Kafka\Message\Decoder\AvroDecoder;
use Jobcloud\Kafka\Message\Decoder\DecoderInterface;
use Jobcloud\Kafka\Message\KafkaConsumerMessageInterface;

/**
 * KafkaAutoDecoder
 *
 * Smart decoder that auto-detects message encoding. When the message starts with
 * the Confluent wire format magic byte (0x00), it first attempts JSON Schema
 * decoding (payload bytes 5+ are valid JSON), then falls back to Avro decoding.
 * Messages without the magic byte are attempted as plain JSON, or returned raw.
 *
 * @category Library
 * @package  Libraries
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class KafkaAutoDecoder implements DecoderInterface
{
    /**
     * KafkaAutoDecoder constructor
     *
     * @param AvroDecoder            $avroDecoder       Avro decoder instance backed by schema registry
     * @param JsonSchemaDecoder|null $jsonSchemaDecoder JSON Schema decoder instance (optional)
     */
    public function __construct(
        private AvroDecoder $avroDecoder,
        private ?JsonSchemaDecoder $jsonSchemaDecoder = null
    ) {
    }

    /**
     * Decode a Kafka consumer message
     *
     * Attempts decoding in this order for wire-format messages (magic byte 0x00):
     * 1. JSON Schema (payload after header is valid JSON text)
     * 2. Avro (binary payload after header)
     *
     * For non-wire-format messages:
     * 3. Plain JSON (starts with { or [)
     * 4. Raw passthrough
     *
     * @param KafkaConsumerMessageInterface $consumerMessage Incoming Kafka message
     *
     * @return KafkaConsumerMessageInterface
     */
    public function decode(KafkaConsumerMessageInterface $consumerMessage): KafkaConsumerMessageInterface
    {
        $body = $consumerMessage->getBody();

        if (is_string($body) && strlen($body) > 5) {
            // Confluent wire format: magic byte 0x00
            if (ord($body[0]) === 0) {
                // Try JSON Schema first (payload is UTF-8 JSON text)
                $jsonDecoded = JsonSchemaDecoder::tryDecode($body);
                if ($jsonDecoded !== null) {
                    if ($this->jsonSchemaDecoder !== null) {
                        try {
                            return $this->jsonSchemaDecoder->decode($consumerMessage);
                        } catch (\Throwable) {
                            // Fall through to return basic decoded
                        }
                    }
                    return new KafkaDecodedMessage($consumerMessage, $jsonDecoded);
                }

                // Fall back to Avro (binary payload)
                try {
                    return $this->avroDecoder->decode($consumerMessage);
                } catch (\Throwable) {
                    // fallback to raw
                }
            }

            // Plain JSON: starts with { or [
            $firstChar = ltrim($body)[0] ?? '';
            if ($firstChar === '{' || $firstChar === '[') {
                $decoded = json_decode($body, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return new KafkaDecodedMessage($consumerMessage, $decoded);
                }
            }
        }

        return $consumerMessage;
    }
}
