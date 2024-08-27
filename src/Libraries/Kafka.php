<?php 

declare(strict_types=1);

/**
 * PHP version 8
 *
 * @category Library
 * @package  Exceptions
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.4
 * @link     https://github.com/brispot
 */

namespace Spotlibs\PhpLib\Libraries;

use GuzzleHttp\Client;
use Jobcloud\Kafka\Message\KafkaAvroSchema;
use Jobcloud\Kafka\Message\KafkaAvroSchemaInterface;
use Jobcloud\Kafka\Message\Registry\AvroSchemaRegistry;
use FlixTech\AvroSerializer\Objects\RecordSerializer;
use FlixTech\SchemaRegistryApi\Registry\CachedRegistry;
use FlixTech\SchemaRegistryApi\Registry\BlockingRegistry;
use FlixTech\SchemaRegistryApi\Registry\PromisingRegistry;
use FlixTech\SchemaRegistryApi\Registry\Cache\AvroObjectCacheAdapter;
use Jobcloud\Kafka\Producer\KafkaProducerBuilder;
use Jobcloud\Kafka\Message\Encoder\AvroEncoder;
use Jobcloud\Kafka\Message\Encoder\JsonEncoder;
use Jobcloud\Kafka\Consumer\KafkaConsumerBuilder;
use Jobcloud\Kafka\Consumer\KafkaConsumerInterface;
use Jobcloud\Kafka\Message\Decoder\AvroDecoder;
use Jobcloud\Kafka\Message\Decoder\JsonDecoder;
use Jobcloud\Kafka\Producer\KafkaProducerInterface;

class Kafka 
{
    const SCHEMALESS = 1; // Tidak berskema
    const SCHEMALESS_WITH_SERDE = 2; // Tidak berskema tapi memiliki mekanisme encode dan decode
    const SCHEMAFULL_WITH_SERDE = 3; // Berskema sekaligus memiliki mekanisme encode dan decode

	public function publishOn(string $topic_name, int $schematype, string $definitionSchemaBody = null, string $definitionSchemaKey = null) : KafkaProducerInterface
	{
		if(env('KAFKA_SCHEME_REGISTRY_URL') === null) {
			throw new \Exception('Env KAFKA_SCHEME_REGISTRY_URL not provide yet');
		}

		if(env('KAFKA_USER_PRODUCE') === null) {
			throw new \Exception('Env KAFKA_USER_PRODUCE not provide yet');
		}

		if(env('KAFKA_PASS_PRODUCE') === null) {
			throw new \Exception('Env KAFKA_PASS_PRODUCE not provide yet');
		}

		if($schematype === self::SCHEMAFULL_WITH_SERDE) {
			$cachedRegistry = new CachedRegistry(
	            new BlockingRegistry(
	                new PromisingRegistry(
	                    new Client([
	                        'base_uri' => env('KAFKA_SCHEME_REGISTRY_URL'),
	                        'auth' => [env('KAFKA_USER_PRODUCE'), env('KAFKA_PASS_PRODUCE')]
	                    ])
	                )
	            ),
	            new AvroObjectCacheAdapter()
	        );

	        $registry = new AvroSchemaRegistry($cachedRegistry);
	        $recordSerializer = new RecordSerializer($cachedRegistry, [
	            RecordSerializer::OPTION_REGISTER_MISSING_SUBJECTS => true, // otomatis di daftarkan subjectny jika belum ada di registry
	            RecordSerializer::OPTION_REGISTER_MISSING_SCHEMAS => true, // otomatis di daftarkan schemany jika belum ada di registry
	        ]);

            if($definitionSchemaBody !== null) {
                $registry->addBodySchemaMappingForTopic(
                    $topic_name,
                    new KafkaAvroSchema($topic_name . '-value', KafkaAvroSchemaInterface::LATEST_VERSION, \AvroSchema::parse($definitionSchemaBody))
                );
            }
            
            if($definitionSchemaKey !== null) {
                $registry->addKeySchemaMappingForTopic(
                    $topic_name,
                    new KafkaAvroSchema($topic_name . '-key', KafkaAvroSchemaInterface::LATEST_VERSION, \AvroSchema::parse($definitionSchemaKey))
                );
            }

            $encoder = new AvroEncoder($registry, $recordSerializer);
		}

        if($schematype === self::SCHEMALESS_WITH_SERDE) {
            $encoder = new JsonEncoder();
        }

        $producerBuilder = KafkaProducerBuilder::create()
            ->withAdditionalConfig(
                [
                    'compression.codec' => 'lz4',
                    'sasl.username' => env('KAFKA_USER_PRODUCE'),
                    'sasl.password' => env('KAFKA_PASS_PRODUCE'),
                    'sasl.mechanism' => 'PLAIN',
                    'security.protocol' => 'SASL_SSL',
                    'message.timeout.ms' => '8000',
                    'socket.timeout.ms' => '8000'
                ]
            )
            ->withAdditionalBroker(env('KAFKA_BROKERS_URL', ''))
            ->withDeliveryReportCallback([KafkaCallable::class, 'deliveryReportCallback'])
            ->withLogCallback([KafkaCallable::class, 'logCallback'])
            ->withErrorCallback([KafkaCallable::class, 'errorProduceCallback']);

        if($schematype === self::SCHEMALESS_WITH_SERDE || $schematype === self::SCHEMAFULL_WITH_SERDE) {
            $producerBuilder->withEncoder($encoder);
        }

        $producer = $producerBuilder->build();

        return $producer;
	}

	public function consumeOn(string $topic_name, int $schematype, string $congrup_name = null) : KafkaConsumerInterface
	{
		if(env('KAFKA_SCHEME_REGISTRY_URL') === null) {
			throw new \Exception('Env KAFKA_SCHEME_REGISTRY_URL not provide yet');
		}

		if(env('KAFKA_USER_CONSUME') === null) {
			throw new \Exception('Env KAFKA_USER_CONSUME not provide yet');
		}

		if(env('KAFKA_PASS_CONSUME') === null) {
			throw new \Exception('Env KAFKA_PASS_CONSUME not provide yet');
		}

        if($schematype === self::SCHEMAFULL_WITH_SERDE) {
            $cachedRegistry = new CachedRegistry(
                new BlockingRegistry(
                    new PromisingRegistry(
                        new Client([
                            'base_uri' => env('KAFKA_SCHEME_REGISTRY_URL'),
                            'auth' => [env('KAFKA_USER_CONSUME'), env('KAFKA_PASS_CONSUME')]
                        ])
                    )
                ),
                new AvroObjectCacheAdapter()
            );

            $registry = new AvroSchemaRegistry($cachedRegistry);
            $recordSerializer = new RecordSerializer($cachedRegistry);

            //if no version is defined, latest version will be used
            //if no schema definition is defined, the appropriate version will be fetched form the registry
            $registry->addBodySchemaMappingForTopic(
                $topic_name,
                new KafkaAvroSchema($topic_name . '-value', KafkaAvroSchemaInterface::LATEST_VERSION)
            );
            $registry->addKeySchemaMappingForTopic(
                $topic_name,
                new KafkaAvroSchema($topic_name . '-key', KafkaAvroSchemaInterface::LATEST_VERSION)
            );
            
            $decoder = new AvroDecoder($registry, $recordSerializer);
        }

        if($schematype === self::SCHEMALESS_WITH_SERDE) {
            $decoder = new JsonDecoder();
        }
        

        if($schematype === self::SCHEMALESS_WITH_SERDE || $schematype === self::SCHEMAFULL_WITH_SERDE) {
            $consumer = KafkaConsumerBuilder::create()->withAdditionalConfig(
                [
                    'client.id' => env('APP_NAME').'-'.gethostname(),
                    'sasl.username' => env('KAFKA_USER_CONSUME'),
                    'sasl.password' => env('KAFKA_PASS_CONSUME'),
                    'sasl.mechanism' => 'PLAIN',
                    'security.protocol' => 'SASL_SSL',
                    'socket.timeout.ms' => '10000'
                ]
            )->withAdditionalBroker(env('KAFKA_BROKERS_URL', ''))
            ->withConsumerGroup($topic_name.'_'.(isset($congrup_name) ? $congrup_name : 'congrup'))
            ->withAdditionalSubscription($topic_name)
            ->withDecoder($decoder)
            ->withErrorCallback([KafkaCallable::class, 'errorConsumeCallback'])
            ->withRebalanceCallback([KafkaCallable::class, 'rebalanceCallback'])
            ->withConsumeCallback([KafkaCallable::class, 'consumeCallback'])
            ->withLogCallback([KafkaCallable::class, 'logCallback'])
            ->withOffsetCommitCallback([KafkaCallable::class, 'offsetCommitCallback'])
            ->build();
        } else {
            $consumer = KafkaConsumerBuilder::create()->withAdditionalConfig(
                [
                    'client.id' => env('APP_NAME').'-'.gethostname(),
                    'sasl.username' => env('KAFKA_USER_CONSUME'),
                    'sasl.password' => env('KAFKA_PASS_CONSUME'),
                    'sasl.mechanism' => 'PLAIN',
                    'security.protocol' => 'SASL_SSL',
                    'socket.timeout.ms' => '5000'
                ]
            )->withAdditionalBroker(env('KAFKA_BROKERS_URL', ''))
            ->withConsumerGroup($topic_name . '_' . (isset($congrup_name) ? $congrup_name : 'congrup'))
            ->withAdditionalSubscription($topic_name)
            ->withErrorCallback([KafkaCallable::class, 'errorConsumeCallback'])
            ->withRebalanceCallback([KafkaCallable::class, 'rebalanceCallback'])
            ->withConsumeCallback([KafkaCallable::class, 'consumeCallback'])
            ->withLogCallback([KafkaCallable::class, 'logCallback'])
            ->withOffsetCommitCallback([KafkaCallable::class, 'offsetCommitCallback'])
            ->build();
        }

        $consumer->subscribe();

        return $consumer;
	}
}