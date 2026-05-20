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

use Jobcloud\Kafka\Message\Encoder\EncoderInterface;
use Jobcloud\Kafka\Message\KafkaProducerMessageInterface;
use Spotlibs\PhpLib\Exceptions\ParameterException;

/**
 * JsonSchemaEncoder
 *
 * Encodes Kafka producer messages using JSON Schema with Confluent wire format.
 * Registers the schema with the Schema Registry, validates the payload, and
 * prepends the wire format header (magic byte + schema ID) to the JSON payload.
 *
 * @category Library
 * @package  Libraries
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class JsonSchemaEncoder implements EncoderInterface
{
    /**
     * Confluent wire format magic byte
     */
    private const MAGIC_BYTE = "\x00";

    /**
     * JsonSchemaEncoder constructor
     *
     * @param JsonSchemaRegistry   $registry   Schema registry client
     * @param KafkaJsonSchema      $bodySchema Body schema definition
     * @param KafkaJsonSchema|null $keySchema  Key schema definition (optional)
     */
    public function __construct(
        private JsonSchemaRegistry $registry,
        private KafkaJsonSchema $bodySchema,
        private ?KafkaJsonSchema $keySchema = null
    ) {
    }

    /**
     * Encode a Kafka producer message with JSON Schema wire format
     *
     * @param KafkaProducerMessageInterface $producerMessage Message to encode
     *
     * @return KafkaProducerMessageInterface Encoded message with wire format body
     * @throws ParameterException
     */
    public function encode(KafkaProducerMessageInterface $producerMessage): KafkaProducerMessageInterface
    {
        $body = $producerMessage->getBody();
        $encodedBody = $this->encodePayload($body, $this->bodySchema);
        $producerMessage = $producerMessage->withBody($encodedBody);

        if ($this->keySchema !== null && $producerMessage->getKey() !== null) {
            $key = $producerMessage->getKey();
            $keyPayload = is_string($key) ? $key : json_encode($key);
            $encodedKey = $this->encodeRawPayload($keyPayload, $this->keySchema);
            $producerMessage = $producerMessage->withKey($encodedKey);
        }

        return $producerMessage;
    }

    /**
     * Encode a payload value with schema validation and wire format
     *
     * @param mixed           $payload Payload data (array or string)
     * @param KafkaJsonSchema $schema  Schema to validate and encode against
     *
     * @return string Wire-format encoded string
     * @throws ParameterException
     */
    private function encodePayload(mixed $payload, KafkaJsonSchema $schema): string
    {
        $jsonString = is_string($payload) ? $payload : json_encode($payload);

        $this->validatePayload($jsonString, $schema->getDefinition());

        return $this->encodeRawPayload($jsonString, $schema);
    }

    /**
     * Prepend Confluent wire format header to a JSON payload
     *
     * @param string          $jsonPayload JSON string payload
     * @param KafkaJsonSchema $schema      Schema (must have resolved ID)
     *
     * @return string Wire-format bytes
     * @throws ParameterException
     */
    private function encodeRawPayload(string $jsonPayload, KafkaJsonSchema $schema): string
    {
        $schemaId = $schema->getSchemaId();

        if ($schemaId === null) {
            $schemaId = $this->registry->register($schema->getSubject(), $schema->getDefinition());
            $schema->setSchemaId($schemaId);
        }

        return self::MAGIC_BYTE . pack('N', $schemaId) . $jsonPayload;
    }

    /**
     * Validate a JSON payload against a JSON Schema definition
     *
     * @param string $jsonPayload      JSON string to validate
     * @param string $schemaDefinition JSON Schema definition string
     *
     * @return void
     * @throws ParameterException
     */
    private function validatePayload(string $jsonPayload, string $schemaDefinition): void
    {
        $data = json_decode($jsonPayload);
        $schema = json_decode($schemaDefinition);

        if ($schema === null) {
            throw new ParameterException('Invalid JSON Schema definition');
        }

        if ($data === null && $jsonPayload !== 'null') {
            throw new ParameterException('Invalid JSON payload');
        }

        // Validate required fields if schema defines them
        if (isset($schema->required) && is_array($schema->required) && is_object($data)) {
            foreach ($schema->required as $field) {
                if (!property_exists($data, $field)) {
                    throw new ParameterException("JSON Schema validation failed: missing required field '{$field}'");
                }
            }
        }

        // Validate property types if schema defines them
        if (isset($schema->properties) && is_object($data)) {
            foreach ($schema->properties as $prop => $propSchema) {
                if (!property_exists($data, $prop)) {
                    continue;
                }
                $this->validateType($data->$prop, $propSchema, $prop);
            }
        }
    }

    /**
     * Validate a value against its declared JSON Schema type
     *
     * @param mixed  $value      Value to check
     * @param object $propSchema Property schema definition
     * @param string $field      Field name for error messages
     *
     * @return void
     * @throws ParameterException
     */
    private function validateType(mixed $value, object $propSchema, string $field): void
    {
        if (!isset($propSchema->type)) {
            return;
        }

        $types = is_array($propSchema->type) ? $propSchema->type : [$propSchema->type];

        foreach ($types as $type) {
            if ($this->matchesType($value, $type)) {
                return;
            }
        }

        throw new ParameterException(
            "JSON Schema validation failed: field '{$field}' does not match type(s) " . json_encode($propSchema->type)
        );
    }

    /**
     * Check if a value matches a JSON Schema type
     *
     * @param mixed  $value Value to check
     * @param string $type  JSON Schema type name
     *
     * @return bool
     */
    private function matchesType(mixed $value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value),
            'integer' => is_int($value),
            'number' => is_int($value) || is_float($value),
            'boolean' => is_bool($value),
            'array' => is_array($value),
            'object' => is_object($value),
            'null' => $value === null,
            default => true,
        };
    }
}
