<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Libraries
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.3.7
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Libraries;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Spotlibs\PhpLib\Logs\Log;

/**
 * GraphQLClient
 *
 * Name for GraphQLClient
 *
 * @category HttpClient
 * @package  Client
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */

class GraphQLClient
{
    /**
     * HTTP client for making requests
     *
     * @var GuzzleClient $httpClient
     */
    private GuzzleClient $httpClient;

    /**
     * GraphQL endpoint URL
     *
     * @var string $endpoint
     */
    private string $endpoint;

    /**
     * Request headers
     *
     * @var array $headers
     */
    private array $headers = [];

    /**
     * Basic auth credentials
     *
     * @var array $basicAuth
     */
    private array $basicAuth = [];

    /**
     * Create a new GraphQLClient instance.
     *
     * @param string $endpoint GraphQL endpoint URL
     * @param array  $config   Additional GuzzleHttp client configuration
     *
     * @return void
     */
    public function __construct(string $endpoint, array $config = [])
    {
        $this->endpoint = $endpoint;

        $defaultConfig = [
            'timeout' => 10,
            'verify' => false,
        ];

        $this->httpClient = new GuzzleClient(array_merge($defaultConfig, $config));

        // Set default headers for GraphQL
        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Set basic authentication credentials
     *
     * @param string $username Username for basic auth
     * @param string $password Password for basic auth
     *
     * @return self
     */
    public function setBasicAuth(string $username, string $password): self
    {
        $this->basicAuth = [$username, $password];
        return $this;
    }

    /**
     * Set JWT bearer token authentication
     *
     * @param string $token JWT token
     *
     * @return self
     */
    public function setBearerToken(string $token): self
    {
        $this->headers['Authorization'] = 'Bearer ' . $token;
        return $this;
    }

    /**
     * Set additional request headers
     *
     * @param array $headers Associative array of headers
     *
     * @return self
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Execute GraphQL query
     *
     * @param string      $query         GraphQL query string
     * @param array|null  $variables     Optional variables for the query
     * @param string|null $operationName Optional operation name
     *
     * @return ResponseInterface GraphQL response
     */
    public function query(string $query, ?array $variables = null, ?string $operationName = null): ResponseInterface
    {
        $startTime = microtime(true);

        $body = ['query' => $query];

        if ($variables !== null) {
            $body['variables'] = $variables;
        }

        if ($operationName !== null) {
            $body['operationName'] = $operationName;
        }

        $jsonBody = json_encode($body, JSON_THROW_ON_ERROR);

        $request = new Request('POST', $this->endpoint, $this->headers, $jsonBody);

        $options = [];

        // Add basic auth if set
        if (!empty($this->basicAuth)) {
            $options['auth'] = $this->basicAuth;
        }

        $response = $this->httpClient->send($request, $options);

        $elapsed = microtime(true) - $startTime;

        if (env('APP_DEBUG', false)) {
            $request->getBody()->rewind();
            $reqBody = $request->getBody()->getContents();
            $respBody = $response->getBody()->getContents();

            if (strlen($reqBody) > 5000) {
                $reqBody = "more than 5000 characters";
            }
            if (strlen($respBody) > 5000) {
                $respBody = "more than 5000 characters";
            }

            $logData = [
                'host' => $request->getUri()->getHost(),
                'url' => $request->getUri()->getPath(),
                'graphql' => [
                    'query' => $query,
                    'variables' => $variables,
                    'operationName' => $operationName,
                ],
                'request' => [
                    'method' => $request->getMethod(),
                    'headers' => $request->getHeaders(),
                    'body' => json_decode($reqBody, true),
                ],
                'response' => [
                    'headers' => $response->getHeaders(),
                    'body' => json_decode($respBody, true),
                ],
                'responseTime' => round($elapsed * 1000),
                'memoryUsage' => memory_get_usage()
            ];

            $response->getBody()->rewind();
            Log::activity()->info($logData);
        }

        return $response;
    }
}
