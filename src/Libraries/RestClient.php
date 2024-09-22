<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Libraries
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.3.0
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Libraries;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Spotlibs\PhpLib\Services\ContextService;
use StdClass;

/**
 * RestClient
 *
 * @category Library
 * @package  Libraries
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */

class RestClient extends Client
{
    private array $header;
    private string $method;
    private int $timeout;
    private bool $verify;
    private ?Response $response;
    private ContextService $contextService;

    /**
     * Create a new controller instance.
     *
     * @return self
     */
    public function __construct()
    {
        parent::__construct();

        $this->contextService = app(ContextService::class);

        $this->header = [];
        $this->method = $this->contextService->get('method') ?? 'POST';
        $this->timeout = 10;
        $this->verify = false;
        $this->response = null;

        if ($this->contextService->get('User-Agent') !== null) {
            $this->header['User-Agent'] = $this->contextService->get('User-Agent');
        }
        if ($this->contextService->get('Content-Type') !== null) {
            $this->header['Content-Type'] = $this->contextService->get('Content-Type');
        }
        if ($this->contextService->get('Accept') !== null) {
            $this->header['Accept'] = $this->contextService->get('Accept');
        }
        if ($this->contextService->get('Accept-Encoding') !== null) {
            $this->header['Accept-Encoding'] = $this->contextService->get('Accept-Encoding');
        }
        if ($this->contextService->get('Cache-Control') !== null) {
            $this->header['Cache-Control'] = $this->contextService->get('Cache-Control');
        }
        if ($this->contextService->get('X-Forwarded-For') !== null) {
            $this->header['X-Forwarded-For'] = $this->contextService->get('X-Forwarded-For');
        }
        if ($this->contextService->get('X-Request-From') !== null) {
            $this->header['X-Request-From'] = $this->contextService->get('X-Request-From');
        }
        if ($this->contextService->get('X-Device-ID') !== null) {
            $this->header['X-Device-ID'] = $this->contextService->get('X-Device-ID');
        }
        if ($this->contextService->get('X-App') !== null) {
            $this->header['X-App'] = $this->contextService->get('X-App');
        }
        if ($this->contextService->get('X-Version-App') !== null) {
            $this->header['X-Version-App'] = $this->contextService->get('X-Version-App');
        }
        if ($this->contextService->get('X-Request-ID') !== null) {
            $this->header['X-Request-ID'] = $this->contextService->get('X-Request-ID');
        }
        if ($this->contextService->get('X-Request-User') !== null) {
            $this->header['X-Request-User'] = $this->contextService->get('X-Request-User');
        }
        if ($this->contextService->get('X-Request-Nama') !== null) {
            $this->header['X-Request-Nama'] = $this->contextService->get('X-Request-Nama');
        }
        if ($this->contextService->get('X-Request-Kode-Jabatan') !== null) {
            $this->header['X-Request-Kode-Jabatan'] = $this->contextService->get('X-Request-Kode-Jabatan');
        }
        if ($this->contextService->get('X-Request-Nama-Jabatan') !== null) {
            $this->header['X-Request-Nama-Jabatan'] = $this->contextService->get('X-Request-Nama-Jabatan');
        }
        if ($this->contextService->get('X-Request-Kode-Uker') !== null) {
            $this->header['X-Request-Kode-Uker'] = $this->contextService->get('X-Request-Kode-Uker');
        }
        if ($this->contextService->get('X-Request-Nama-Uker') !== null) {
            $this->header['X-Request-Nama-Uker'] = $this->contextService->get('X-Request-Nama-Uker');
        }
        if ($this->contextService->get('X-Request-Jenis-Uker') !== null) {
            $this->header['X-Request-Jenis-Uker'] = $this->contextService->get('X-Request-Jenis-Uker');
        }
        if ($this->contextService->get('X-Request-Kode-MainUker') !== null) {
            $this->header['X-Request-Kode-MainUker'] = $this->contextService->get('X-Request-Kode-MainUker');
        }
        if ($this->contextService->get('X-Request-Kode-Region') !== null) {
            $this->header['X-Request-Kode-Region'] = $this->contextService->get('X-Request-Kode-Region');
        }
        if ($this->contextService->get('X-Path-Gateway') !== null) {
            $this->header['X-Path-Gateway'] = $this->contextService->get('X-Path-Gateway');
        }
        if ($this->contextService->get('Authorization') !== null) {
            $this->header['Authorization'] = $this->contextService->get('Authorization');
        }
        if ($this->contextService->get('X-Api-Key') !== null) {
            $this->header['X-Api-Key'] = $this->contextService->get('X-Api-Key');
        }
    }

    /**
     * Add multiple custom headers to RestClient if necessary
     *
     * @param array $headers array of headers to add in key-value pairs
     *
     * @return void
     */
    public function addHeaders(array $headers): void
    {
        foreach ($headers as $idx => $value) {
            if ($idx != null) {
                $this->header[$idx] = $value;
            }
        }
    }

    /**
     * Add a custom header to RestClient if necessary
     *
     * @param string $key   header key
     * @param string $value header value
     *
     * @return void
     */
    public function addHeader(string $key, string $value): void
    {
        $this->header[$key] = $value;
    }

    /**
     * Remove a custom header from RestClient
     *
     * @param string $key header key
     *
     * @return void
     */
    public function removeHeader(string $key): void
    {
        if (isset($this->header[$key])) {
            unset($this->header[$key]);
        }
    }

    /**
     * Set timeout for response
     *
     * @param int $timeout timeout in seconds
     *
     * @return void
     */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * Perform http request to given url
     *
     * @param mixed  $jsonBody request body in json format
     * @param string $base_uri base URI to request
     * @param string $uri      URI to request
     * @param string $method   request method (POST, GET, etc)
     *
     * @return void
     */
    public function call(mixed $jsonBody, string $base_uri, string $uri = '/', string $method = ''): StdClass
    {
        $client = new Client(['base_uri' => $base_uri, 'allow_redirects' => true, 'timeout' => $this->timeout, 'verify' => $this->verify, 'headers' => $this->header, 'json' => $jsonBody]);
        $this->response = $client->request(!empty($method) ? strtoupper($method) : strtoupper($this->method), $uri);
        $clientResponse = json_decode($this->response->getBody()->getContents(), null, 512, JSON_THROW_ON_ERROR);
        $clientResponse->responseDesc = trim(preg_replace('/Ln.\d+/i', '', isset($clientResponse->responseDesc) ? $clientResponse->responseDesc : ''));
        return $clientResponse;
    }

    /**
     * Perform asynchronous/non blocking http request to given url
     *
     * @param mixed  $jsonBody request body in json format
     * @param string $base_uri base URI to request
     * @param string $uri      URI to request
     * @param string $method   request method (POST, GET, etc)
     *
     * @return PromiseInterface
     */
    public function callAsync(mixed $jsonBody, string $base_uri, string $uri = '/', string $method = ''): PromiseInterface
    {
        $client = new Client(['base_uri' => $base_uri, 'allow_redirects' => true, 'timeout' => $this->timeout, 'verify' => $this->verify, 'headers' => $this->header, 'json' => $jsonBody]);
        $promise = $client->requestAsync(!empty($method) ? strtoupper($method) : strtoupper(string: $this->method), $uri);
        return $promise;
    }

    /**
     * Get response from RestClient
     *
     * @return \Illuminate\Http\Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }
}
