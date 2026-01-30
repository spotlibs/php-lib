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
 * Kafka producer client for publishing messages with schema support
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
     * Current producer instance
     *
     * @var KafkaProducer|null
     */
    private static ?KafkaProducer $producer = null;

    /**
     * Current consumer instance
     *
     * @var KafkaConsumerInterface|null
     */
    private static ?KafkaConsumerInterface $consumer = null;

    /**
     * Current topic name
     *
     * @var string|null
     */
    private static ?string $currentTopic = null;

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
    public static function publishOn(
        string $topic,
        int $schemaType,
        ?string $schemaBody = null,
        ?string $schemaKey = null,
        array $additionalConfig = []
    ): KafkaProducer {
        self::validateEnvironment();
        self::$currentTopic = $topic;

        $producerBuilder = self::createProducerBuilder($additionalConfig);

        if ($schemaType === self::AVRO_SCHEMA) {
            $encoder = self::createAvroEncoder($topic, $schemaBody, $schemaKey);
            $producerBuilder->withEncoder($encoder);
        } elseif ($schemaType === self::JSON_SCHEMA) {
            $encoder = new JsonEncoder();
            $producerBuilder->withEncoder($encoder);
        }

        self::$producer = $producerBuilder->build();

        Log::runtime()->info(
            [
                'operation' => 'kafka_producer_initialized',
                'topic' => $topic,
                'schemaType' => $schemaType
            ]
        );

        return self::$producer;
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
    public static function produce(mixed $body, ?string $key = null, int $partition = 0): void
    {
        self::ensureProducerInitialized();

        $message = KafkaProducerMessage::create(self::$currentTopic, $partition)
            ->withBody($body);

        if ($key !== null) {
            $message = $message->withKey($key);
        }

        self::$producer->produce($message);
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
    public static function produceWithHeaders(
        mixed $body,
        array $headers,
        ?string $key = null,
        int $partition = 0
    ): void {
        self::ensureProducerInitialized();

        $message = KafkaProducerMessage::create(self::$currentTopic, $partition)
            ->withBody($body)
            ->withHeaders($headers);

        if ($key !== null) {
            $message = $message->withKey($key);
        }

        self::$producer->produce($message);
    }

    /**
     * Produce multiple messages in batch
     *
     * @param array $messages Array of messages with format: ['body' => mixed, 'key' => ?string, 'partition' => int]
     *
     * @return void
     * @throws ParameterException
     */
    public static function produceBatch(array $messages): void
    {
        self::ensureProducerInitialized();

        foreach ($messages as $msg) {
            $body = $msg['body'] ?? null;
            $key = $msg['key'] ?? null;
            $partition = $msg['partition'] ?? 0;

            if ($body === null) {
                continue;
            }

            self::produce($body, $key, $partition);
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
    public static function flush(int $timeoutMs = 10000): void
    {
        self::ensureProducerInitialized();

        $startTime = microtime(true);
        self::$producer->flush($timeoutMs);
        $elapsed = microtime(true) - $startTime;

        Log::runtime()->info(
            [
                'operation' => 'kafka_flush',
                'topic' => self::$currentTopic,
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
    public static function close(int $timeoutMs = 10000): void
    {
        if (self::$producer !== null) {
            self::flush($timeoutMs);
            self::$producer = null;
            self::$currentTopic = null;

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
    public static function publish(
        string $topic,
        mixed $body,
        int $schemaType = self::SCHEMALESS,
        ?string $schemaBody = null,
        ?string $schemaKey = null,
        ?string $key = null,
        int $partition = 0,
        int $flushTimeoutMs = 10000
    ): void {
        self::publishOn($topic, $schemaType, $schemaBody, $schemaKey);
        self::produce($body, $key, $partition);
        self::flush($flushTimeoutMs);
    }

    /**
     * Validate required environment variables
     *
     * @return void
     * @throws ParameterException
     */
    private static function validateEnvironment(): void
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
    private static function createProducerBuilder(array $additionalConfig): KafkaProducerBuilder
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
            ->withDeliveryReportCallback([self::class, 'deliveryReportCallback'])
            ->withErrorCallback([self::class, 'errorCallback'])
            ->withLogCallback([self::class, 'logCallback']);
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
    private static function createAvroEncoder(
        string $topic,
        ?string $schemaBody,
        ?string $schemaKey
    ): AvroEncoder {
        if (empty(env('KAFKA_SCHEMA_REGISTRY_URL'))) {
            throw new ParameterException('Environment variable KAFKA_SCHEMA_REGISTRY_URL is not set');
        }

        $cachedRegistry = new CachedRegistry(
            new BlockingRegistry(
                new PromisingRegistry(
                    new GuzzleClient(
                        [
                            'base_uri' => env('KAFKA_SCHEMA_REGISTRY_URL'),
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
    private static function ensureProducerInitialized(): void
    {
        if (self::$producer === null) {
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
    public static function deliveryReportCallback(mixed $kafka, mixed $message): void
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
    public static function errorCallback(mixed $kafka, int $err, string $reason): void
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
    public static function logCallback(mixed $kafka, int $level, string $facility, string $message): void
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
    public static function consumeOn(
        string $topic,
        int $schemaType,
        ?string $consumerGroup = null,
        array $additionalConfig = []
    ): KafkaConsumerInterface {
        self::validateConsumerEnvironment();
        self::$currentTopic = $topic;

        $consumerBuilder = self::createConsumerBuilder($topic, $consumerGroup, $additionalConfig);

        if ($schemaType === self::AVRO_SCHEMA) {
            $decoder = self::createAvroDecoder($topic);
            $consumerBuilder->withDecoder($decoder);
        } elseif ($schemaType === self::JSON_SCHEMA) {
            $decoder = new JsonDecoder();
            $consumerBuilder->withDecoder($decoder);
        }

        self::$consumer = $consumerBuilder->build();
        self::$consumer->subscribe();

        Log::runtime()->info(
            [
                'operation' => 'kafka_consumer_initialized',
                'topic' => $topic,
                'consumerGroup' => $consumerGroup,
                'schemaType' => $schemaType
            ]
        );

        return self::$consumer;
    }

    /**
     * Consume single message
     *
     * @param int $timeoutMs Timeout in milliseconds
     *
     * @return KafkaConsumerMessageInterface
     * @throws ParameterException
     */
    public static function consume(int $timeoutMs = 10000): KafkaConsumerMessageInterface
    {
        if (self::$consumer === null) {
            throw new ParameterException('Consumer not initialized. Call consumeOn() first.');
        }

        return self::$consumer->consume($timeoutMs);
    }

    /**
     * Commit message offset
     *
     * @param mixed $message Message to commit
     *
     * @return void
     * @throws ParameterException
     */
    public static function commit(mixed $message): void
    {
        if (self::$consumer === null) {
            throw new ParameterException('Consumer not initialized. Call consumeOn() first.');
        }

        self::$consumer->commit($message);
    }

    /**
     * Validate required consumer environment variables
     *
     * @return void
     * @throws ParameterException
     */
    private static function validateConsumerEnvironment(): void
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
    public static function subscribe(): void
    {
        if (self::$consumer === null) {
            throw new ParameterException('Consumer not initialized. Call consumeOn() first.');
        }

        self::$consumer->subscribe();

        Log::runtime()->info(
            [
                'operation' => 'kafka_consumer_subscribed',
                'topic' => self::$currentTopic
            ]
        );
    }

    /**
     * Close consumer and cleanup resources
     *
     * @return void
     */
    public static function closeConsumer(): void
    {
        if (self::$consumer !== null) {
            self::$consumer->unsubscribe();
            self::$consumer = null;
            self::$currentTopic = null;

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
    private static function createConsumerBuilder(
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
            ->withErrorCallback([self::class, 'errorCallback'])
            ->withRebalanceCallback([self::class, 'rebalanceCallback'])
            ->withConsumeCallback([self::class, 'consumeCallback'])
            ->withLogCallback([self::class, 'logCallback'])
            ->withOffsetCommitCallback([self::class, 'offsetCommitCallback']);
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
    private static function createAvroDecoder(string $topic): AvroDecoder
    {
        if (empty(env('KAFKA_SCHEMA_REGISTRY_URL'))) {
            throw new ParameterException('Environment variable KAFKA_SCHEMA_REGISTRY_URL is not set');
        }

        $cachedRegistry = new CachedRegistry(
            new BlockingRegistry(
                new PromisingRegistry(
                    new GuzzleClient(
                        [
                            'base_uri' => env('KAFKA_SCHEMA_REGISTRY_URL'),
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
}
