<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Libraries
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.1
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Libraries;

use GuzzleHttp\Client as BaseClient;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Spotlibs\PhpLib\Exceptions\StdException;

/**
 * ClientProxy
 *
 * HTTP client with proxy support and file/binary response handling.
 * Intended for calls that go through an outbound proxy to the internet.
 * Internal metadata/propagation headers are intentionally NOT forwarded.
 *
 * Proxy URL is read from the PROXY_URL environment variable and can be
 * overridden at runtime via setProxy().
 *
 * For JSON responses the same responseCode validation as Client applies.
 * For binary responses (PDF, image, ZIP, etc.) JSON decoding is skipped
 * and the raw stream is returned intact for the caller to consume.
 *
 * @category HttpClient
 * @package  Client
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class ClientProxy extends BaseClient
{
    /**
     * Timeout in seconds, default is 30 seconds
     *
     * @var float $timeout
     */
    public float $timeout = 30;

    /**
     * Set to true to enable SSL certificate verification
     *
     * @var bool $verify
     */
    public bool $verify = false;

    /**
     * Proxy URL used for all outgoing requests
     *
     * @var string $proxyUrl
     */
    protected string $proxyUrl = '';

    /**
     * Request headers appended on every call
     *
     * @var array $requestHeaders
     */
    protected array $requestHeaders = [];

    /**
     * Additional response headers injected before returning
     *
     * @var array $responseHeaders
     */
    protected array $responseHeaders = [];

    /**
     * Create a new ClientProxy instance.
     *
     * Reads PROXY_URL from the environment automatically.
     * No internal metadata headers are propagated — this client is for
     * outbound internet calls where those headers must not be sent.
     *
     * @param array<mixed> $config Guzzle config options
     *
     * @return void
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $proxyUrl = env('PROXY_URL', '');
        if (!empty($proxyUrl)) {
            $this->proxyUrl = $proxyUrl;
        }
    }

    /**
     * Override the proxy URL at runtime
     *
     * @param string $proxyUrl Proxy URL, e.g. http://proxy.internal:8080
     *
     * @return self
     */
    public function setProxy(string $proxyUrl): self
    {
        $this->proxyUrl = $proxyUrl;
        return $this;
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
     * Execute an HTTP request through the configured proxy.
     *
     * Behaviour by response Content-Type:
     * - application/json  : validates responseCode == '00', throws StdException otherwise.
     *                       Rewinds the stream before returning so the caller can read it again.
     * - binary/file types : skips JSON validation entirely; returns the raw stream as-is.
     *                       The caller is responsible for reading or streaming the body.
     *
     * @param Request $request HTTP Request instance
     * @param array   $options Guzzle HTTP client options
     *
     * @return ResponseInterface
     */
    public function call(Request $request, array $options = []): ResponseInterface
    {
        $options = $this->buildOptions($options);

        foreach ($this->requestHeaders as $key => $header) {
            $request = $request->withHeader($key, $header);
        }

        $response = $this->send($request, $options);

        foreach ($this->responseHeaders as $key => $header) {
            $response = $response->withHeader($key, $header);
        }

        if ($response->getStatusCode() === 200 && $this->isJsonResponse($response)) {
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

        return $response;
    }

    /**
     * Execute an HTTP request intended to download a file (PDF, image, ZIP, etc.).
     *
     * Unlike call(), this method never attempts to JSON-decode the response body.
     * The raw stream is returned directly so the caller can pipe it into a Laravel
     * StreamedResponse or save it to disk.
     *
     * Example:
     *   $response = $client->callFile($request);
     *   $contentType  = $response->getHeaderLine('Content-Type');
     *   $stream       = $response->getBody();
     *
     * @param Request $request HTTP Request instance
     * @param array   $options Guzzle HTTP client options
     *
     * @return ResponseInterface Raw response with binary stream intact
     */
    public function callFile(Request $request, array $options = []): ResponseInterface
    {
        $options = $this->buildOptions($options);

        // Stream the response to avoid loading the entire file into memory
        $options['stream'] = true;

        foreach ($this->requestHeaders as $key => $header) {
            $request = $request->withHeader($key, $header);
        }

        $response = $this->send($request, $options);

        foreach ($this->responseHeaders as $key => $header) {
            $response = $response->withHeader($key, $header);
        }

        return $response;
    }

    /**
     * Determine whether the response carries a JSON body based on Content-Type header.
     * When Content-Type is absent we assume JSON to preserve backwards-compatible behaviour.
     *
     * @param ResponseInterface $response HTTP response
     *
     * @return bool
     */
    private function isJsonResponse(ResponseInterface $response): bool
    {
        $contentType = $response->getHeaderLine('Content-Type');
        return str_contains($contentType, 'application/json') || empty($contentType);
    }

    /**
     * Merge caller-supplied Guzzle options with proxy, timeout, and verify defaults
     *
     * @param array $options Caller-supplied Guzzle options
     *
     * @return array Merged options
     */
    private function buildOptions(array $options): array
    {
        if (!isset($options['timeout'])) {
            $options['timeout'] = $this->timeout;
        }
        if (!isset($options['verify'])) {
            $options['verify'] = $this->verify;
        }
        if (!empty($this->proxyUrl) && !isset($options['proxy'])) {
            $options['proxy'] = $this->proxyUrl;
        }
        return $options;
    }
}
