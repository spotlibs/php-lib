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
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Spotlibs\PhpLib\Logs\Log;

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
class ClientExternal extends BaseClient
{
    /**
     * Timeout in seconds, default is 10 seconds
     *
     * @var float $timeout
     */
    public float $timeout = 10;
    /**
     * Set to true to enable SSL certificate verification and use the default CA bundle provided by operating system
     *
     * @var bool $verify
     */
    public bool $verify = false;
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
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Set the timeout for Http Client
     *
     * @param float $timeout number of desired timeout
     *
     * @return self
     */
    public function setTimeout(float $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Set verify
     *
     * @param bool $verify number of desired timeout
     *
     * @return self
     */
    public function setVerify(bool $verify): self
    {
        $this->verify = $verify;
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
     * Set the timeout for Http Client
     *
     * @param Request $request HTTP Request instance
     *
     * @return ResponseInterface
     */
    public function call(Request $request): ResponseInterface
    {
        $startime = microtime(true);
        $options = ['timeout' => $this->timeout, 'verify' => $this->verify];
        foreach ($this->requestHeaders as $key => $header) {
            $request = $request->withHeader($key, $header);
        }
        if (!$request->hasHeader('Content-Type')) {
            $request = $request->withHeader('Content-Type', 'application/json');
        }
        $response = $this->send($request, $options);
        foreach ($this->responseHeaders as $key => $header) {
            $response = $response->withHeader($key, $header);
        }
        $elapsed = microtime(true) - $startime;
        if (env('APP_DEBUG', false)) {
            $request->getBody()->rewind();
            if (strlen($reqbody = $request->getBody()->getContents()) > 5000) {
                $reqbody = "more than 5000 characters";
            }
            if (strlen($respbody = $response->getBody()->getContents()) > 5000) {
                $respbody = "more than 5000 characters";
            }
            $logData = [
                'host' => $request->getUri()->getHost(),
                'url' => $request->getUri()->getPath(),
                'request' => [
                    'headers' => $request->getHeaders(),
                    'body' => json_decode($reqbody, true)
                ],
                'response' => [
                    'headers' => $response->getHeaders(),
                    'body' => json_decode($respbody, true)
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
