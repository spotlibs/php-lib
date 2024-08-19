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
use Brispot\PhpLib\Exceptions\TraitException;
use Brispot\PhpLib\Exceptions\ExceptionFactory;

/**
 * Class RuntimeException
 *
 * @category Library
 * @package  Exceptions
 * @author   Nur Arif Prihutomo <nur.arif@corp.bri.co.id>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/brispot
 */
class RuntimeException extends Exception implements ExceptionInterface
{
    use TraitException;

    /* Properties */
    private string $errorCode;
    private string $errorMessage;
    private int $httpCode;
    private mixed $data;

    /**
     * Create a new RuntimeException instance.
     *
     * @param string $message Message of Exception
     * @param mixed  $data    Optional data when exception has response data
     *
     * @return void
     */
    public function __construct(
        string $message = 'Runtime error happens',
        mixed $data = null
    ) {
        $this->errorCode = ExceptionFactory::RUNTIME_EXCEPTION;
        $this->errorMessage = $message;
        $this->httpCode = 200;
        $this->data = $data;
        parent::__construct($message, 1, null);
    }
}
