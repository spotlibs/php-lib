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

use Carbon\Exceptions\InvalidTypeException;
use Exception;
use GuzzleHttp\Client as BaseClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Spotlibs\PhpLib\Libraries\ClientHelpers\Multipart;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        $options = ['timeout' => $this->timeout, 'verify' => $this->verify];
        foreach ($this->requestHeaders as $key => $header) {
            $request->withHeader($key, $header);
        }
        $response = $this->send($request, $options);
        foreach ($this->responseHeaders as $key => $header) {
            $response->withHeader($key, $header);
        }
        return $response;
    }
}
