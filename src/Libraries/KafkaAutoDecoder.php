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

namespace Spotlibs\PhpLib\Libraries;

use Jobcloud\Kafka\Message\Decoder\AvroDecoder;
use Jobcloud\Kafka\Message\Decoder\DecoderInterface;
use Jobcloud\Kafka\Message\KafkaConsumerMessageInterface;

/**
 * KafkaAutoDecoder
 *
 * Smart decoder that auto-detects Avro wire format (magic byte 0x00) and
 * falls back to raw/schemaless if the byte is absent or decoding fails.
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
     * @param AvroDecoder $avroDecoder Avro decoder instance backed by schema registry
     */
    public function __construct(private AvroDecoder $avroDecoder)
    {
    }

    /**
     * Decode a Kafka consumer message
     *
     * Attempts Avro decoding when the message body starts with the Avro magic
     * byte (0x00). Falls back to returning the raw message as-is for schemaless
     * messages or when Avro decoding fails.
     *
     * @param KafkaConsumerMessageInterface $consumerMessage Incoming Kafka message
     *
     * @return KafkaConsumerMessageInterface
     */
    public function decode(KafkaConsumerMessageInterface $consumerMessage): KafkaConsumerMessageInterface
    {
        $body = $consumerMessage->getBody();

        if (is_string($body) && strlen($body) > 5 && ord($body[0]) === 0) {
            try {
                return $this->avroDecoder->decode($consumerMessage);
            } catch (\Throwable) {
                // fallback to raw schemaless
            }
        }

        return $consumerMessage;
    }
}
