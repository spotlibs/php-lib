<?php

declare(strict_types=1);

namespace Tests\Dtos;

use Spotlibs\PhpLib\Dtos\TraitDtos;

class Human
{
    use TraitDtos;
    
    public string $name;
    public int $age;
    public string $dob;
    public bool $is_alive;
    public Dog $dog;
}