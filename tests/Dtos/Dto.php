<?php

declare(strict_types=1);

namespace Tests\Dtos;

use Carbon\Carbon;
use Spotlibs\PhpLib\Dtos\TraitConvertibleDtos;
use Spotlibs\PhpLib\Dtos\TraitDtos;

class Dto
{
    use TraitConvertibleDtos;
    public string $name;
    public int $age;
    public float $salary;
    public float $gpa;
    public array $chores;
    public bool $married;
    public ?string $referal;
    public Company $company;
    public Carbon $createdAt;
    public string $dob;
}