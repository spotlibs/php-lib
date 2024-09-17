<?php

declare(strict_types=1);

namespace Tests\Dtos;

class Vehicle
{
    protected string $brand;
    protected string $type;
    protected int $capacity_cc;
    public function __construct(string $brand, string $type, int $capacity_cc)
    {
        $this->brand = $brand;
        $this->type = $type;
        $this->capacity_cc = $capacity_cc;
    }
}