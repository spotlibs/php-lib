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
 * Class HeaderException
 *
 * @category Library
 * @package  Exceptions
 * @author   Nur Arif Prihutomo <ayip.eiger@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class HeaderException extends Exception implements ExceptionInterface
{
    use TraitException;

    /* Properties */
    private string $errorCode;
    private string $errorMessage;
    private int $httpCode;
    private mixed $data;

    /**
     * Create a new HeaderException instance.
     *
     * @param string $message Message of Exception
     * @param mixed  $data    Optional data when exception has response data
     *
     * @return void
     */
    public function __construct(?string $message = null, mixed $data = null)
    {
        $this->errorCode = StdException::HEADER_EXCEPTION;
        $this->errorMessage = $message ?? 'Header Request tidak valid';
        $this->httpCode = 400;
        $this->data = $data;
        parent::__construct($this->errorMessage, 1, null);
    }
}
