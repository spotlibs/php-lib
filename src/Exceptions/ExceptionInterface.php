<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Exceptions
 * @author   Nur Arif Prihutomo <nur.arif@corp.bri.co.id>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.1
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Exceptions;

/**
 * Interface ExceptionInterface for all implementation Exception Class
 *
 * @category Library
 * @package  Exceptions
 * @author   Nur Arif Prihutomo <nur.arif@corp.bri.co.id>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */
interface ExceptionInterface
{
    /**
     * Get attribute errorCode
     *
     * @return string
     */
    public function getErrorCode(): string;

    /**
     * Get attribute errorMessage
     *
     * @return string
     */
    public function getErrorMessage(): string;

    /**
     * Get attribute httpCode
     *
     * @return int
     */
    public function getHttpCode(): int;

    /**
     * Get attribute data
     *
     * @return mixed
     */
    public function getData(): mixed;
}
