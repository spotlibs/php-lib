<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Exceptions
 * @author   Nur Arif Prihutomo <nur.arif@corp.bri.co.id>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.1
 * @link     https://github.com/brispot
 */

declare(strict_types=1);

namespace Brispot\PhpLib\Exceptions;

use Exception;
use Brispot\PhpLib\Exceptions\ExceptionInterface;

/**
 * Class AccessException
 *
 * @category Library
 * @package  Exceptions
 * @author   Nur Arif Prihutomo <nur.arif@corp.bri.co.id>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/brispot
 */
class AccessException extends Exception implements ExceptionInterface
{
    /* Properties */
    private string $errorCode;
    private string $errorMessage;
    private int $httpCode;
    private mixed $data;

    /**
     * Create a new AccessException instance.
     *
     * @param string $message Message of Exception
     * @param mixed  $data    Optional data when exception has response data
     *
     * @return void
     */
    public function __construct(string $message = 'Akses tidak diijinkan', mixed $data = null)
    {
        $this->errorCode = 'X1';
        $this->errorMessage = $message;
        $this->httpCode = 403;
        $this->data = $data;
        parent::__construct($message, 1, null);
    }

    /**
     * Get attribute errorCode
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get attribute errorMessage
     *
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * Get attribute httpCode
     *
     * @return int
     */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * Get attribute data
     *
     * @return mixed
     */
    public function getData(): mixed
    {
        return $this->data;
    }
}
