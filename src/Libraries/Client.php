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
        parent::__construct($config);
        $context = app(Context::class);
        /**
         * Metadata variable
         *
         * @var Metadata $meta
         */
        $meta = $context->get(Metadata::class);
        if (!is_null($meta)) {
            if (isset($meta->user_agent) && $meta->user_agent !== null) {
                $this->requestHeaders['User-Agent'] = $meta->user_agent;
            }
            if (isset($meta->cache_control) && $meta->cache_control !== null) {
                $this->requestHeaders['Cache-Control'] = $meta->cache_control;
            }
            if (isset($meta->forwarded_for) && $meta->forwarded_for !== null) {
                $this->requestHeaders['X-Forwarded-For'] = $meta->forwarded_for;
            }
            if (isset($meta->request_from) && $meta->request_from !== null) {
                $this->requestHeaders['X-Request-From'] = $meta->request_from;
            }
            if (isset($meta->device_id) && $meta->device_id !== null) {
                $this->requestHeaders['X-Device-ID'] = $meta->device_id;
            }
            if (isset($meta->app) && $meta->app !== null) {
                $this->requestHeaders['X-App'] = $meta->app;
            }
            if (isset($meta->version_app) && $meta->version_app !== null) {
                $this->requestHeaders['X-Version-App'] = $meta->version_app;
            }
            if (isset($meta->req_id) && $meta->req_id !== null) {
                $this->requestHeaders['X-Request-ID'] = $meta->req_id;
            }
            if (isset($meta->req_user) && $meta->req_user !== null) {
                $this->requestHeaders['X-Request-User'] = $meta->req_user;
            }
            if (isset($meta->req_nama) && $meta->req_nama !== null) {
                $this->requestHeaders['X-Request-Nama'] = $meta->req_nama;
            }
            if (isset($meta->req_kode_jabatan) && $meta->req_kode_jabatan !== null) {
                $this->requestHeaders['X-Request-Kode-Jabatan'] = $meta->req_kode_jabatan;
            }
            if (isset($meta->req_nama_jabatan) && $meta->req_nama_jabatan !== null) {
                $this->requestHeaders['X-Request-Nama-Jabatan'] = $meta->req_nama_jabatan;
            }
            if (isset($meta->req_kode_uker) && $meta->req_kode_uker !== null) {
                $this->requestHeaders['X-Request-Kode-Uker'] = $meta->req_kode_uker;
            }
            if (isset($meta->req_nama_uker) && $meta->req_nama_uker !== null) {
                $this->requestHeaders['X-Request-Nama-Uker'] = $meta->req_nama_uker;
            }
            if (isset($meta->req_jenis_uker) && $meta->req_jenis_uker !== null) {
                $this->requestHeaders['X-Request-Jenis-Uker'] = $meta->req_jenis_uker;
            }
            if (isset($meta->req_kode_main_uker) && $meta->req_kode_main_uker !== null) {
                $this->requestHeaders['X-Request-Kode-MainUker'] = $meta->req_kode_main_uker;
            }
            if (isset($meta->req_kode_region) && $meta->req_kode_region !== null) {
                $this->requestHeaders['X-Request-Kode-Region'] = $meta->req_kode_region;
            }
            if (isset($meta->path_gateway) && $meta->path_gateway !== null) {
                $this->requestHeaders['X-Path-Gateway'] = $meta->path_gateway;
            }
            if (isset($meta->authorization) && $meta->authorization !== null) {
                $this->requestHeaders['Authorization'] = $meta->authorization;
            }
            if (isset($meta->api_key) && $meta->api_key !== null) {
                $this->requestHeaders['X-Api-Key'] = $meta->api_key;
            }
            if (isset($meta->req_role) && $meta->req_role !== null) {
                $this->requestHeaders['X-Request-Role'] = $meta->req_role;
            }
        }
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
