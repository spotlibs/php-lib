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
     * @param int $timeout number of desired timeout
     *
     * @return self
     */
    public function setTimeout(int $timeout): self
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
     * Set request body
     *
     * @param array $body number of desired timeout
     *
     * @return self
     */
    public function setRequestBody(array $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Set request body in associative array
     *
     * @param string $form_type RequestOptions::FORM_PARAMS|JSON|MULTIPART|QUERY|...
     *
     * @return self
     */
    public function setFormType(string $form_type): self
    {
        $allowed = [
            RequestOptions::FORM_PARAMS,
            RequestOptions::JSON,
            RequestOptions::MULTIPART,
            RequestOptions::QUERY,
            RequestOptions::BODY
        ];
        if (!in_array($form_type, $allowed)) {
            throw new Exception('form type not allowed. supporting ' . implode(", ", $allowed));
        }
        $this->request_body_type = $form_type;
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
        $this->responseHeaders = $headers;
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
        $body = [];
        if (!empty($this->body)) {
            if ($this->request_body_type == RequestOptions::MULTIPART) {
                $temp = [];
                $key_of_contents = [];
                foreach ($this->body as $key => $b) {
                    if (! $b instanceof Multipart) {
                        throw new InvalidTypeException('Request body does not comply multipart form-data structure');
                    }
                    if (is_array($b->contents)) {
                        $key_of_contents[] = $key;
                        /**
                         * Check if contents is array of files
                         *
                         * @var array $b->contents
                         */
                        if (isset($b->contents[0]) && $b->contents[0] instanceof \Illuminate\Http\UploadedFile) {
                            $z = $b->contents;
                            /**
                             * Array $b->contents
                             *
                             * @var \Illuminate\Http\UploadedFile[] $z
                             */
                            foreach ($z as $v) {
                                /**
                                 * Multipart
                                 *
                                 * @var \Illuminate\Http\UploadedFile $v multipart
                                 */
                                $y = new Multipart(['name' => $b->name, 'headers' => ['Content-Type' => $v->getMimeType()]]);
                                $y->contents = fopen($v->getRealPath(), 'r');
                                array_push($temp, $y);
                            }
                        }
                    } else {
                        $x = $this->body[$key];
                        /**
                         * Multipart
                         *
                         * @var Multipart $x multipart
                         */
                        if ($x->contents instanceof \Illuminate\Http\UploadedFile) {
                            $z = $x->contents;
                            /**
                             * Uploaded file
                             *
                             * @var \Illuminate\Http\UploadedFile $z uploaded file
                             */
                            $x->contents = fopen($z->getRealPath(), 'r');
                        }
                        $this->body[$key] = $x->toArray();
                    }
                }
                if (count($temp) > 0) {
                    foreach ($key_of_contents as $key) {
                        unset($this->body[$key]);
                    }
                    $this->body = array_values($this->body);
                    $this->body = array_merge($this->body, $temp);
                }
            }
            $body = [
                $this->request_body_type => $this->body
            ];
        }
        $options = ['timeout' => $this->timeout];
        $options = array_merge($options, $body);
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
