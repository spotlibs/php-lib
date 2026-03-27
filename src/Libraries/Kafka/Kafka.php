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

use AvroSchema;
use FlixTech\AvroSerializer\Objects\RecordSerializer;
use FlixTech\SchemaRegistryApi\Registry\BlockingRegistry;
use FlixTech\SchemaRegistryApi\Registry\Cache\AvroObjectCacheAdapter;
use FlixTech\SchemaRegistryApi\Registry\CachedRegistry;
use FlixTech\SchemaRegistryApi\Registry\PromisingRegistry;
use GuzzleHttp\Client as GuzzleClient;
use Jobcloud\Kafka\Consumer\KafkaConsumerBuilder;
use Jobcloud\Kafka\Consumer\KafkaConsumerBuilderInterface;
use Jobcloud\Kafka\Consumer\KafkaConsumerInterface;
use Jobcloud\Kafka\Message\Decoder\AvroDecoder;
use Jobcloud\Kafka\Message\Decoder\JsonDecoder;
use Jobcloud\Kafka\Message\Encoder\AvroEncoder;
use Jobcloud\Kafka\Message\Encoder\JsonEncoder;
use Jobcloud\Kafka\Message\KafkaAvroSchema;
use Jobcloud\Kafka\Message\KafkaAvroSchemaInterface;
use Jobcloud\Kafka\Message\KafkaConsumerMessageInterface;
use Jobcloud\Kafka\Message\KafkaProducerMessage;
use Jobcloud\Kafka\Message\Registry\AvroSchemaRegistry;
use Jobcloud\Kafka\Producer\KafkaProducer;
use Jobcloud\Kafka\Producer\KafkaProducerBuilder;
use Spotlibs\PhpLib\Exceptions\ParameterException;
use Spotlibs\PhpLib\Logs\Log;

/**
 * Kafka
 *
 * Kafka producer/consumer client for publishing and consuming messages with
 * schema support. Use the Kafka() helper to retrieve an instance.
 *
 * @category Library
 * @package  Libraries
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class Kafka
{
    /**
     * Schema type: No schema, no encoding (raw data)
     */
    public const SCHEMALESS = 1;

    /**
     * Schema type: JSON schema with validation and encoding
     */
    public const JSON_SCHEMA = 2;

    /**
     * Schema type: Avro schema with registry and encoding
     */
    public const AVRO_SCHEMA = 3;

    /**
     * Schema type: Auto-detect -> Avro if magic byte (\x00) present, else raw
     */
    public const AUTO_SCHEMA = 4;


    /**
     * Current producer instance
     *
     * @var KafkaProducer|null
     */
    private ?KafkaProducer $producer = null;

    /**
     * Current consumer instance
     *
     * @var KafkaConsumerInterface|null
     */
    private ?KafkaConsumerInterface $consumer = null;

    /**
     * Current topic name
     *
     * @var string|null
     */
    private ?string $currentTopic = null;

    /**
     * Setup and return Kafka producer
     *
     * @param string      $topic            Topic name
     * @param int         $schemaType       Schema type constant
     * @param string|null $schemaBody       Schema definition for message body
     * @param string|null $schemaKey        Schema definition for message key
     * @param array       $additionalConfig Additional Kafka configuration
     *
     * @return KafkaProducer
     */
    public function publishOn(
        string $topic,
        int $schemaType,
        ?string $schemaBody = null,
        ?string $schemaKey = null,
        array $additionalConfig = []
    ): KafkaProducer {
        $this->validateEnvironment();
        $this->currentTopic = $topic;

        $producerBuilder = $this->createProducerBuilder($additionalConfig);

        if ($schemaType === self::AVRO_SCHEMA) {
            $encoder = $this->createAvroEncoder($topic, $schemaBody, $schemaKey);
            $producerBuilder = $producerBuilder->withEncoder($encoder);
        } elseif ($schemaType === self::JSON_SCHEMA) {
            $encoder = new JsonEncoder();
            $producerBuilder = $producerBuilder->withEncoder($encoder);
        }

        $this->producer = $producerBuilder->build();

        Log::runtime()->info(
            [
                'operation' => 'kafka_producer_initialized',
                'topic' => $topic,
                'schemaType' => $schemaType
            ]
        );

        return $this->producer;
    }

    /**
     * Produce a message to the current topic
     *
     * @param mixed       $body      Message body
     * @param string|null $key       Message key for partitioning
     * @param int         $partition Partition number (default 0)
     *
     * @return void
     * @throws ParameterException
     */
    public function produce(mixed $body, ?string $key = null, int $partition = 0): void
    {
        $this->ensureProducerInitialized();

        $message = KafkaProducerMessage::create($this->currentTopic, $partition)
            ->withBody($body);

        if ($key !== null) {
            $message = $message->withKey($key);
        }

        $this->producer->produce($message);
    }

    /**
     * Produce a message with custom headers
     *
     * @param mixed       $body      Message body
     * @param array       $headers   Message headers (key-value pairs)
     * @param string|null $key       Message key for partitioning
     * @param int         $partition Partition number (default 0)
     *
     * @return void
     * @throws ParameterException
     */
    public function produceWithHeaders(
        mixed $body,
        array $headers,
        ?string $key = null,
        int $partition = 0
    ): void {
        $this->ensureProducerInitialized();

        $message = KafkaProducerMessage::create($this->currentTopic, $partition)
            ->withBody($body)
            ->withHeaders($headers);

        if ($key !== null) {
            $message = $message->withKey($key);
        }

        $this->producer->produce($message);
    }

    /**
     * Produce multiple messages in batch
     *
     * @param array $messages Array of messages with format: ['body' => mixed, 'key' => ?string, 'partition' => int]
     *
     * @return void
     * @throws ParameterException
     */
    public function produceBatch(array $messages): void
    {
        $this->ensureProducerInitialized();

        foreach ($messages as $msg) {
            $body = $msg['body'] ?? null;
            $key = $msg['key'] ?? null;
            $partition = $msg['partition'] ?? 0;

            if ($body === null) {
                continue;
            }

            $this->produce($body, $key, $partition);
        }
    }

    /**
     * Flush all queued messages to Kafka
     *
     * @param int $timeoutMs Timeout in milliseconds (default 10000)
     *
     * @return void
     * @throws ParameterException
     */
    public function flush(int $timeoutMs = 10000): void
    {
        $this->ensureProducerInitialized();

        $startTime = microtime(true);
        $this->producer->flush($timeoutMs);
        $elapsed = microtime(true) - $startTime;

        Log::runtime()->info(
            [
                'operation' => 'kafka_flush',
                'topic' => $this->currentTopic,
                'responseTime' => round($elapsed * 1000)
            ]
        );
    }

    /**
     * Close producer and cleanup resources
     *
     * @param int $timeoutMs Timeout in milliseconds to flush remaining messages (default 10000)
     *
     * @return void
     */
    public function close(int $timeoutMs = 10000): void
    {
        if ($this->producer !== null) {
            $this->flush($timeoutMs);
            $this->producer = null;
            $this->currentTopic = null;

            Log::runtime()->info(
                [
                    'operation' => 'kafka_producer_closed'
                ]
            );
        }
    }

    /**
     * Convenience method: publish single message immediately
     *
     * @param string      $topic          Topic name
     * @param mixed       $body           Message body
     * @param int         $schemaType     Schema type constant (default SCHEMALESS)
     * @param string|null $schemaBody     Schema definition for body
     * @param string|null $schemaKey      Schema definition for key
     * @param string|null $key            Message key
     * @param int         $partition      Partition number (default 0)
     * @param int         $flushTimeoutMs Flush timeout in milliseconds (default 10000)
     *
     * @return void
     * @throws ParameterException
     */
    public function publish(
        string $topic,
        mixed $body,
        int $schemaType = self::SCHEMALESS,
        ?string $schemaBody = null,
        ?string $schemaKey = null,
        ?string $key = null,
        int $partition = 0,
        int $flushTimeoutMs = 10000
    ): void {
        $this->publishOn($topic, $schemaType, $schemaBody, $schemaKey);
        $this->produce($body, $key, $partition);
        $this->flush($flushTimeoutMs);
    }

    /**
     * Validate required environment variables
     *
     * @return void
     * @throws ParameterException
     */
    private function validateEnvironment(): void
    {
        $required = [
            'KAFKA_BROKERS_URL' => 'Kafka brokers URL',
            'KAFKA_USER_PRODUCE' => 'Kafka producer username',
            'KAFKA_PASS_PRODUCE' => 'Kafka producer password'
        ];

        foreach ($required as $env => $description) {
            if (empty(env($env))) {
                throw new ParameterException("Environment variable {$env} ({$description}) is not set");
            }
        }
    }

    /**
     * Create producer builder with default configuration
     *
     * @param array $additionalConfig Additional configuration
     *
     * @return KafkaProducerBuilder
     */
    private function createProducerBuilder(array $additionalConfig): KafkaProducerBuilder
    {
        $defaultConfig = [
            'compression.codec' => 'lz4',
            'sasl.username' => env('KAFKA_USER_PRODUCE'),
            'sasl.password' => env('KAFKA_PASS_PRODUCE'),
            'sasl.mechanism' => 'PLAIN',
            'security.protocol' => 'SASL_SSL',
            'message.timeout.ms' => '10000',
            'socket.timeout.ms' => '10000'
        ];

        $config = array_merge($defaultConfig, $additionalConfig);

        return KafkaProducerBuilder::create()
            ->withAdditionalConfig($config)
            ->withAdditionalBroker(env('KAFKA_BROKERS_URL'))
            ->withDeliveryReportCallback([$this, 'deliveryReportCallback'])
            ->withErrorCallback([$this, 'errorCallback'])
            ->withLogCallback([$this, 'logCallback']);
    }

    /**
     * Create Avro encoder with schema registry
     *
     * @param string      $topic      Topic name
     * @param string|null $schemaBody Body schema definition
     * @param string|null $schemaKey  Key schema definition
     *
     * @return AvroEncoder
     * @throws ParameterException
     */
    private function createAvroEncoder(
        string $topic,
        ?string $schemaBody,
        ?string $schemaKey
    ): AvroEncoder {
        if (empty(env('KAFKA_SCHEME_REGISTRY_URL'))) {
            throw new ParameterException('Environment variable KAFKA_SCHEME_REGISTRY_URL is not set');
        }

        $cachedRegistry = new CachedRegistry(
            new BlockingRegistry(
                new PromisingRegistry(
                    new GuzzleClient(
                        [
                            'base_uri' => env('KAFKA_SCHEME_REGISTRY_URL'),
                            'auth' => [env('KAFKA_USER_PRODUCE'), env('KAFKA_PASS_PRODUCE')]
                        ]
                    )
                )
            ),
            new AvroObjectCacheAdapter()
        );

        $registry = new AvroSchemaRegistry($cachedRegistry);
        $recordSerializer = new RecordSerializer(
            $cachedRegistry,
            [
                RecordSerializer::OPTION_REGISTER_MISSING_SUBJECTS => true,
                RecordSerializer::OPTION_REGISTER_MISSING_SCHEMAS => true,
            ]
        );

        if ($schemaBody !== null) {
            $registry->addBodySchemaMappingForTopic(
                $topic,
                new KafkaAvroSchema(
                    $topic . '-value',
                    KafkaAvroSchemaInterface::LATEST_VERSION,
                    AvroSchema::parse($schemaBody)
                )
            );
        }

        if ($schemaKey !== null) {
            $registry->addKeySchemaMappingForTopic(
                $topic,
                new KafkaAvroSchema(
                    $topic . '-key',
                    KafkaAvroSchemaInterface::LATEST_VERSION,
                    AvroSchema::parse($schemaKey)
                )
            );
        }

        return new AvroEncoder($registry, $recordSerializer);
    }

    /**
     * Ensure producer is initialized
     *
     * @return void
     * @throws ParameterException
     */
    private function ensureProducerInitialized(): void
    {
        if ($this->producer === null) {
            throw new ParameterException('Producer not initialized. Call publishOn() first.');
        }
    }

    /**
     * Delivery report callback
     *
     * @param mixed $kafka   Kafka instance
     * @param mixed $message Message
     *
     * @return void
     */
    public function deliveryReportCallback(mixed $kafka, mixed $message): void
    {
        if ($message->err !== 0) {
            Log::runtime()->error(
                [
                    'operation' => 'kafka_delivery_failed',
                    'error' => $message->errstr(),
                    'topic' => $message->topic_name,
                    'partition' => $message->partition
                ]
            );
        }
    }

    /**
     * Error callback
     *
     * @param mixed  $kafka  Kafka instance
     * @param int    $err    Error code
     * @param string $reason Error reason
     *
     * @return void
     */
    public function errorCallback(mixed $kafka, int $err, string $reason): void
    {
        Log::runtime()->error(
            [
                'operation' => 'kafka_producer_error',
                'errorCode' => $err,
                'reason' => $reason
            ]
        );
    }

    /**
     * Log callback
     *
     * @param mixed  $kafka    Kafka instance
     * @param int    $level    Log level
     * @param string $facility Facility
     * @param string $message  Log message
     *
     * @return void
     */
    public function logCallback(mixed $kafka, int $level, string $facility, string $message): void
    {
        if ($level <= 3) {
            Log::runtime()->warning(
                [
                    'operation' => 'kafka_producer_log',
                    'level' => $level,
                    'facility' => $facility,
                    'message' => $message
                ]
            );
        }
    }

    /**
     * Setup and return Kafka consumer
     *
     * @param string      $topic            Topic name
     * @param int         $schemaType       Schema type constant
     * @param string|null $consumerGroup    Consumer group name
     * @param array       $additionalConfig Additional Kafka configuration
     *
     * @return KafkaConsumerInterface
     * @throws ParameterException
     * @throws \AvroIOException
     */
    public function consumeOn(
        string $topic,
        int $schemaType,
        ?string $consumerGroup = null,
        array $additionalConfig = []
    ): KafkaConsumerInterface {
        $this->validateConsumerEnvironment();
        $this->currentTopic = $topic;

        $consumerBuilder = $this->createConsumerBuilder($topic, $consumerGroup, $additionalConfig);

        if ($schemaType === self::AVRO_SCHEMA) {
            $decoder = $this->createAvroDecoder($topic);
            $consumerBuilder = $consumerBuilder->withDecoder($decoder);
        } elseif ($schemaType === self::JSON_SCHEMA) {
            $consumerBuilder = $consumerBuilder->withDecoder(new JsonDecoder());
        } elseif ($schemaType === self::AUTO_SCHEMA) {
            $avroDecoder = $this->createAvroDecoder($topic);
            $consumerBuilder = $consumerBuilder->withDecoder(new KafkaAutoDecoder($avroDecoder));
        }

        $this->consumer = $consumerBuilder->build();
        $this->consumer->subscribe();

        Log::runtime()->info(
            [
                'operation' => 'kafka_consumer_initialized',
                'topic' => $topic,
                'consumerGroup' => $consumerGroup,
                'schemaType' => $schemaType
            ]
        );

        return $this->consumer;
    }

    /**
     * Consume single message
     *
     * @param int     $timeoutMs Timeout in milliseconds
     * @param ?string $topic     Topic
     *
     * @return KafkaConsumerMessageInterface
     */
    public function consume(int $timeoutMs = 10000, ?string $topic = null): KafkaConsumerMessageInterface
    {
        if ($this->consumer === null) {
            if ($topic === null) {
                throw new ParameterException('Consumer not initialized. Call consumeOn() first or provide a topic.');
            }
            // auto-bind with AUTO_SCHEMA
            $this->consumeOn($topic, self::AUTO_SCHEMA);
        }

        return $this->consumer->consume($timeoutMs);
    }

    /**
     * Commit message offset
     *
     * @param mixed $message Message to commit
     *
     * @return void
     * @throws ParameterException
     */
    public function commit(mixed $message): void
    {
        if ($this->consumer === null) {
            throw new ParameterException('Consumer not initialized. Call consumeOn() first.');
        }

        $this->consumer->commit($message);
    }

    /**
     * Validate required consumer environment variables
     *
     * @return void
     * @throws ParameterException
     */
    private function validateConsumerEnvironment(): void
    {
        $required = [
            'KAFKA_BROKERS_URL' => 'Kafka brokers URL',
            'KAFKA_USER_CONSUME' => 'Kafka consumer username',
            'KAFKA_PASS_CONSUME' => 'Kafka consumer password'
        ];

        foreach ($required as $env => $description) {
            if (empty(env($env))) {
                throw new ParameterException("Environment variable {$env} ({$description}) is not set");
            }
        }
    }

    /**
     * Subscribe to topics
     *
     * @return void
     * @throws ParameterException
     */
    public function subscribe(): void
    {
        if ($this->consumer === null) {
            throw new ParameterException('Consumer not initialized. Call consumeOn() first.');
        }

        $this->consumer->subscribe();

        Log::runtime()->info(
            [
                'operation' => 'kafka_consumer_subscribed',
                'topic' => $this->currentTopic
            ]
        );
    }

    /**
     * Close consumer and cleanup resources
     *
     * @return void
     */
    public function closeConsumer(): void
    {
        if ($this->consumer !== null) {
            $this->consumer->unsubscribe();
            $this->consumer = null;
            $this->currentTopic = null;

            Log::runtime()->info(
                [
                    'operation' => 'kafka_consumer_closed'
                ]
            );
        }
    }

    /**
     * Create consumer builder with default configuration
     *
     * @param string      $topic            Topic name
     * @param string|null $consumerGroup    Consumer group
     * @param array       $additionalConfig Additional configuration
     *
     * @return KafkaConsumerBuilderInterface
     */
    private function createConsumerBuilder(
        string $topic,
        ?string $consumerGroup,
        array $additionalConfig
    ): KafkaConsumerBuilderInterface {
        $groupName = $consumerGroup ?? $topic . '_consumer_group';

        $defaultConfig = [
            'client.id' => env('APP_NAME') . '-' . gethostname(),
            'compression.codec' => 'lz4',
            'sasl.username' => env('KAFKA_USER_CONSUME'),
            'sasl.password' => env('KAFKA_PASS_CONSUME'),
            'sasl.mechanism' => 'PLAIN',
            'security.protocol' => 'SASL_SSL',
            'enable.auto.commit' => false,
            'message.timeout.ms' => '10000',
            'socket.timeout.ms' => '10000',
            'request.timeout.ms' => '60000'
        ];

        $config = array_merge($defaultConfig, $additionalConfig);

        return KafkaConsumerBuilder::create()
            ->withAdditionalConfig($config)
            ->withAdditionalBroker((string) env('KAFKA_BROKERS_URL'))
            ->withConsumerGroup($groupName)
            ->withAdditionalSubscription($topic, [], KafkaConsumerBuilderInterface::OFFSET_STORED)
            ->withErrorCallback([$this, 'errorCallback'])
            ->withRebalanceCallback([$this, 'rebalanceCallback'])
            ->withConsumeCallback([$this, 'consumeCallback'])
            ->withLogCallback([$this, 'logCallback'])
            ->withOffsetCommitCallback([$this, 'offsetCommitCallback']);
    }

    /**
     * Create Avro decoder with schema registry
     *
     * @param string $topic Topic name
     *
     * @return AvroDecoder
     * @throws ParameterException
     * @throws \AvroIOException
     */
    private function createAvroDecoder(string $topic): AvroDecoder
    {
        if (empty(env('KAFKA_SCHEME_REGISTRY_URL'))) {
            throw new ParameterException('Environment variable KAFKA_SCHEME_REGISTRY_URL is not set');
        }

        $cachedRegistry = new CachedRegistry(
            new BlockingRegistry(
                new PromisingRegistry(
                    new GuzzleClient(
                        [
                            'base_uri' => env('KAFKA_SCHEME_REGISTRY_URL'),
                            'auth' => [env('KAFKA_USER_CONSUME'), env('KAFKA_PASS_CONSUME')]
                        ]
                    )
                )
            ),
            new AvroObjectCacheAdapter()
        );

        $registry = new AvroSchemaRegistry($cachedRegistry);
        $recordSerializer = new RecordSerializer($cachedRegistry);

        // Fetch schemas from registry automatically
        $registry->addBodySchemaMappingForTopic(
            $topic,
            new KafkaAvroSchema(
                $topic . '-value',
                KafkaAvroSchemaInterface::LATEST_VERSION
            )
        );
        $registry->addKeySchemaMappingForTopic(
            $topic,
            new KafkaAvroSchema(
                $topic . '-key',
                KafkaAvroSchemaInterface::LATEST_VERSION
            )
        );

        return new AvroDecoder($registry, $recordSerializer);
    }

    /**
     * Rebalance callback
     *
     * @param mixed $kafka      Kafka instance
     * @param int   $err        Error code
     * @param array $partitions Partitions
     *
     * @return void
     */
    public function rebalanceCallback(mixed $kafka, int $err, array $partitions): void
    {
        $assignPartitions = defined('RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS')
            ? RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS // Defined on C-level constant, comes from the `ext-rdkafka`
            : -175;

        if ($err === $assignPartitions) {
            $kafka->assign($partitions);
        } else {
            $kafka->assign(null);
        }
    }

    /**
     * Consume callback
     *
     * @param mixed $message Message
     * @param mixed $ctx     Context
     *
     * @return void
     */
    public function consumeCallback(mixed $message, mixed $ctx): void
    {
        // no-op: messages are handled by the caller via consume()
    }

    /**
     * Offset commit callback
     *
     * @param mixed $kafka           Kafka instance
     * @param int   $err             Error code
     * @param array $topicPartitions Topic partitions
     *
     * @return void
     */
    public function offsetCommitCallback(mixed $kafka, int $err, array $topicPartitions): void
    {
        if ($err !== 0) {
            Log::runtime()->error(
                [
                    'operation' => 'kafka_offset_commit_failed',
                    'errorCode' => $err
                ]
            );
        }
    }
}