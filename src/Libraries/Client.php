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

use Exception;
use GuzzleHttp\Client as BaseClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
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
    protected float $timeout = 10;
    protected int $timeout_unit = 1;
    protected array $body = [];
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
     * @param int $unit    time unit like second, milisecond, nanosecond, etc. Use static var from TimeoutUnit for clearer name
     *
     * @return self
     */
    public function setTimeout(int $timeout, float $unit = 1): self
    {
        $this->timeout = $timeout * $unit;
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
     * Set the timeout for Http Client
     *
     * @param Request $request HTTP Request instance
     *
     * @return ResponseInterface
     */
    public function call(Request $request): ResponseInterface
    {
        $body = $request->getBody()->getContents();
        if (!empty($body)) {
            $this->body = [
                $this->request_body_type => json_decode($body, true, 512, JSON_THROW_ON_ERROR)
            ];
        }
        $options = ['timeout' => $this->timeout];
        $options = array_merge($options, $this->body);
        $response = $this->send($request, $options);
        return $response;
    }
}
