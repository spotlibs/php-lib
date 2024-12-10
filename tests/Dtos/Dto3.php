<?php

namespace Tests\Dtos;

use Spotlibs\PhpLib\Dtos\TraitDtos;

class Dto3
{
    use TraitDtos;

    public string $name;
    public int $age;
    public bool $is_married;
}