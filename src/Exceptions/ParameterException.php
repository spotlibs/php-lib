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
 * Class ParameterException
 *
 * @category Library
 * @package  Exceptions
 * @author   Nur Arif Prihutomo <ayip.eiger@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
class ParameterException extends Exception implements ExceptionInterface
{
    use TraitException;

    /* Properties */
    private string $errorCode;
    private string $errorMessage;
    private int $httpCode;
    private mixed $data;
    private array $validationErrors;

    /**
     * Create a new ParameterException instance.
     *
     * @param string $message          Message of Exception
     * @param mixed  $data             Optional data when exception has response data
     * @param array  $validationErrors Optional data when exception has response data
     *
     * @return void
     */
    public function __construct(?string $message = null, mixed $data = null, array $validationErrors = [])
    {
        $this->errorCode = StdException::PARAMETER_EXCEPTION;
        $this->errorMessage = $message ?? 'Parameter tidak sesuai';
        $this->httpCode = 200;
        $this->data = $data;
        $this->validationErrors = $validationErrors;
        parent::__construct($this->errorMessage, 1, null);
    }

    /**
     * Get validation errors only for Parameter Exception type
     *
     * @return mixed
     */
    public function getValidationErrors(): mixed
    {
        return $this->validationErrors;
    }
}
