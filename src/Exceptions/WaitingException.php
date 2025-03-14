<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Exceptions
 * @author   Nur Arif Prihutomo <ayip.eiger@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.1
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Exceptions;

use Exception;
use Spotlibs\PhpLib\Exceptions\ExceptionInterface;
use Spotlibs\PhpLib\Exceptions\StdException;
use Spotlibs\PhpLib\Exceptions\TraitException;

/**
 * Class WaitingException
 *
 * @category Library
 * @package  Exceptions
 * @author   Nur Arif Prihutomo <ayip.eiger@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class WaitingException extends Exception implements ExceptionInterface
{
    use TraitException;

    /* Properties */
    private string $errorCode;
    private string $errorMessage;
    private int $httpCode;
    private mixed $data;

    /**
     * Create a new WaitingException instance.
     *
     * @param string $message Message of Exception
     * @param mixed  $data    Optional data when exception has response data
     *
     * @return void
     */
    public function __construct(?string $message = null, mixed $data = null)
    {
        $this->errorCode = StdException::WAITING_EXCEPTION;
        $this->errorMessage = $message ?? 'Masih proses harap tunggu';
        $this->httpCode = 200;
        $this->data = $data;
        parent::__construct($this->errorMessage, 5, null);
    }
}
