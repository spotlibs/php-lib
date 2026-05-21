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

use GuzzleHttp\Client as GuzzleClient;
use Spotlibs\PhpLib\Exceptions\ParameterException;

/**
 * JsonSchemaRegistry
 *
 * Manages JSON Schema registration and retrieval from Confluent Schema Registry.
 * Handles the REST API calls for registering schemas under subjects and fetching
 * schemas by ID.
 *
 * @category Library
 * @package  Libraries
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class JsonSchemaRegistry
{
    /**
     * In-memory cache of schema ID → schema definition
     *
     * @var array<int, string>
     */
    private array $schemaCache = [];

    /**
     * JsonSchemaRegistry constructor
     *
     * @param GuzzleClient $client Guzzle HTTP client configured with base_uri and auth
     */
    public function __construct(private GuzzleClient $client)
    {
    }

    /**
     * Register a JSON Schema under a subject and return the schema ID
     *
     * @param string $subject    Subject name (e.g. "topic-value")
     * @param string $definition JSON Schema definition string
     *
     * @return int The schema ID assigned by the registry
     * @throws ParameterException
     */
    public function register(string $subject, string $definition): int
    {
        $payload = json_encode(
            [
            'schemaType' => 'JSON',
            'schema' => $definition,
            ]
        );

        try {
            $response = $this->client->post(
                sprintf('subjects/%s/versions', $subject),
                [
                    'headers' => [
                        'Content-Type' => 'application/vnd.schemaregistry.v1+json',
                        'Accept' => 'application/vnd.schemaregistry.v1+json',
                    ],
                    'body' => $payload,
                ]
            );

            $body = json_decode($response->getBody()->getContents(), true);

            if (!isset($body['id'])) {
                throw new ParameterException('Schema Registry did not return a schema ID');
            }

            $schemaId = (int) $body['id'];
            $this->schemaCache[$schemaId] = $definition;

            return $schemaId;
        } catch (ParameterException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ParameterException('Failed to register JSON Schema: ' . $e->getMessage());
        }
    }

    /**
     * Fetch a JSON Schema definition by its schema ID
     *
     * @param int $schemaId Schema ID
     *
     * @return string JSON Schema definition string
     * @throws ParameterException
     */
    public function getSchemaById(int $schemaId): string
    {
        if (isset($this->schemaCache[$schemaId])) {
            return $this->schemaCache[$schemaId];
        }

        try {
            $response = $this->client->get(
                sprintf('schemas/ids/%d', $schemaId),
                [
                    'headers' => [
                        'Accept' => 'application/vnd.schemaregistry.v1+json',
                    ],
                ]
            );

            $body = json_decode($response->getBody()->getContents(), true);

            if (!isset($body['schema'])) {
                throw new ParameterException("Schema not found for ID {$schemaId}");
            }

            $definition = $body['schema'];
            $this->schemaCache[$schemaId] = $definition;

            return $definition;
        } catch (ParameterException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ParameterException('Failed to fetch JSON Schema: ' . $e->getMessage());
        }
    }
}
