<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Libraries
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.3.7
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Libraries;

use GuzzleHttp\Client as BaseClient;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Spotlibs\PhpLib\Exceptions\StdException;
use Spotlibs\PhpLib\Services\Context;
use Spotlibs\PhpLib\Services\Metadata;

/**
 * ClientTimeoutUnit
 *
 * Name for HTTP Client timeout unit
 *
 * @category HttpClient
 * @package  Client
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class Client extends BaseClient
{
    /**
     * Request body, set according to the request
     *
     * @var array $body
     */
    protected array $body = [];
    /**
     * Request header if only headers are not set in the request. Will be appended on call method
     *
     * @var array $requestHeaders
     */
    protected array $requestHeaders = [];
    /**
     * Customize response header
     *
     * @var array $responseHeaders
     */
    protected array $responseHeaders = [];
    /**
     * Body type of the
     *
     * @var array $responseHeaders
     */
    protected string $request_body_type = 'json';

    /**
     * Create a new Client instance.
     *
     * @param array<mixed> $config config of GuzzleHttp Client
     *
     * @return void
     */
    public function __construct(array $config = [])
    {
        $context = app(Context::class);
        $metadata = $context->get(Metadata::class);
        foreach ((array) $metadata as $key => $value) {
            $this->requestHeaders[$key] = $value;
        }
        parent::__construct($config);
    }

    /**
     * Set request headers in associative array
     *
     * @param array<string[]> $headers example: ['Content-Type' => ['application/json']]
     *
     * @return self
     */
    public function injectRequestHeader(array $headers): self
    {
        $this->requestHeaders = $headers;
        return $this;
    }

    /**
     * Set response headers in associative array
     *
     * @param array<string[]> $headers example: ['Content-Type' => ['application/json']]
     *
     * @return self
     */
    public function injectResponseHeader(array $headers): self
    {
        $this->responseHeaders = $headers;
        return $this;
    }

    /**
     * Execute the HTTP request through GuzzleHttp Client
     *
     * @param Request $request HTTP Request instance
     * @param array   $options Guzzle HTTP client options. See more at https://docs.guzzlephp.org/en/stable/request-options.html
     *
     * @return ResponseInterface
     */
    public function call(Request $request, array $options = []): ResponseInterface
    {
        if (!isset($options['timeout'])) {
            $options['timeout'] = 10;
        }
        if (!isset($options['verify'])) {
            $options['verify'] = false;
        }
        foreach ($this->requestHeaders as $key => $header) {
            $request = $request->withHeader($key, $header);
        }
        $response = $this->send($request, $options);
        if ($response->getStatusCode() === 200) {
            $decoded = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            if (isset($decoded['responseCode']) && $decoded['responseCode'] <> '00') {
                throw StdException::create(
                    $decoded['responseCode'],
                    $decoded['responseDesc'],
                    $decoded['responseData'] ?? null,
                    $decoded['validationErrors'] ?? [],
                );
            }
            $response->getBody()->rewind();
        }
        foreach ($this->responseHeaders as $key => $header) {
            $response = $response->withHeader($key, $header);
        }
        return $response;
    }
}
