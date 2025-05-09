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
use Illuminate\Support\Facades\Redis;
use Psr\Http\Message\ResponseInterface;
use Spotlibs\PhpLib\Exceptions\InvalidRuleException;
use Spotlibs\PhpLib\Libraries\MapRoute;
use Spotlibs\PhpLib\Logs\Log;
use Spotlibs\PhpLib\Services\Context;
use Spotlibs\PhpLib\Services\Metadata;
use Throwable;

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
     * @param array<mixed> $config config of GuzzleHttp Client
     *
     * @return void
     */
    public function __construct(array $config = [])
    {
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
        $context = app(Context::class);
        $metadata = $context->get(Metadata::class);
        $startime = microtime(true);
        $uri = $request->getUri();
        $url = $uri->getScheme() . "://" . $uri->getHost();
        $url .= is_null($uri->getPort()) ? "" : ":" . $uri->getPort();
        $url .= $uri->getPath();
        try {
            $maproute = $this->checkMock($url);
            if (!empty((array) $maproute) && $maproute->flag) {
                $request_temp = new Request(
                    $request->getMethod(),
                    $maproute->mock_url,
                    $request->getHeaders(),
                    $request->getBody(),
                    $request->getProtocolVersion()
                );
                $request = $request_temp;
                $request = $request->withHeader('Host', parse_url($maproute->mock_url, PHP_URL_HOST));
                unset($request_temp);
            }
        } catch (Throwable $th) {
            //do nothing
        }
        if (!isset($options['timeout'])) {
            $options['timeout'] = 60;
        }
        foreach ($this->requestHeaders as $key => $header) {
            $request = $request->withHeader($key, $header);
        }
        $response = $this->send($request, $options);
        foreach ($this->responseHeaders as $key => $header) {
            $response = $response->withHeader($key, $header);
        }
        $elapsed = microtime(true) - $startime;
        if (env('APP_DEBUG', false)) {
            $request->getBody()->rewind();
            $reqbody = $request->getBody()->getContents();
            $respbody = $response->getBody()->getContents();
            if (strlen($reqbody) > 5000) {
                $reqbody = "more than 5000 characters";
            }
            if (strlen($respbody) > 5000) {
                $respbody = "more than 5000 characters";
            }
            $logData = [
                'app_name' => env('APP_NAME'),
                'path' => is_null($metadata) ? null : $metadata->identifier,
                'host' => $request->getUri()->getHost(),
                'url' => $request->getUri()->getPath(),
                'request' => [
                    'method' => $request->getMethod(),
                    'headers' => $request->getHeaders(),
                ],
                'response' => [
                    'httpCode' => $response->getStatusCode(),
                    'headers' => $response->getHeaders(),
                ],
                'responseTime' => round($elapsed * 1000),
                'memoryUsage' => memory_get_usage()
            ];
            if ($request->getHeader('Content-Type') == ['application/json']) {
                $logData['request']['body'] = json_decode($reqbody, true);
            } else {
                $logData['request']['body'] = $reqbody;
            }
            if ($response->getHeader('Content-Type') == ['application/json']) {
                $logData['response']['body'] = json_decode($respbody, true);
            } else {
                $logData['response']['body'] = $respbody;
            }
            $response->getBody()->rewind();
            Log::activity()->info($logData);
        }
        return $response;
    }

    /**
     * Check if url shall mock
     *
     * @param string $url full url of the request
     *
     * @return array
     */
    private function checkMock(string $url): MapRoute
    {
        if (env('APP_ENV') == 'production') {
            throw new InvalidRuleException('Cannot use mock in production environment');
        }
        $maproute = Redis::get('eksternal_mock_url_mapping:' . $url);
        $maproute = json_decode($maproute, true, 512, JSON_THROW_ON_ERROR);
        return new MapRoute($maproute);
    }
}
