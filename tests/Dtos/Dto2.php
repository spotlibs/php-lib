<?php

declare(strict_types=1);

namespace Tests\Dtos;
use Spotlibs\PhpLib\Dtos\TraitDtos;

class Dto2
{
    use TraitDtos;

    public string $name;
    public int $employeeId;
    public bool $isActive;
    public array $relatives;
    public Vehicle $vehicle;
    public Human $partner;
    /**
     * Summary of siblings
     * @var Human[] $siblings
     */
    public array $siblings;
    
    protected array $arrayOfObjectMap = [
        'siblings' => Human::class
    ];
}