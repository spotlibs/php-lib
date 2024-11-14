<?php

declare(strict_types=1);

namespace Tests\Dtos;

use Spotlibs\PhpLib\Dtos\TraitDtos;

class Dog
{
    use TraitDtos;

    public string $name;
    public int $age;
    public bool $is_alive;
}