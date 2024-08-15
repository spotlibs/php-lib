<?php

declare(strict_types= 1);

namespace Brispot\PhpLib\Exceptions;

trait TraitException {
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