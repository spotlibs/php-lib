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
    /**
     * Timeout in seconds, default is 10 seconds
     *
     * @var float $timeout
     */
    public float $timeout = 10;
    /**
     * Request body, set according to the request
     *
     * @var array $body
     */
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
     *
     * @return self
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
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
            if ($this->request_body_type == 'json') {
                $arr_body = json_decode($body, true);
                $this->body = [
                    $this->request_body_type => $arr_body
                ];
            } else {
                $this->body = [
                    $this->request_body_type => $body
                ];
            }
        }
        $options = ['timeout' => $this->timeout];
        $options = array_merge($options, $this->body);
        $response = $this->send($request, $options);
        return $response;
    }
}
