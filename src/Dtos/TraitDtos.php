<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Exceptions
 * @author   Made Mas Adi Winata <m45adiwinata@gmail.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.4
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Dtos;

trait TraitDtos
{
    public function __construct(array $data) {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public static function create(array $data): mixed
    {
        $self = new self($data);

        return $self;
    }

    public function toArray(): array
    {
        return (array) $this;
    }

    public function toJson()
    {
        $data = $this->toArray();
        return json_encode($data);
    }
}