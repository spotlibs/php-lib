<?php

declare(strict_types=1);

namespace Tests\Dtos;

use Carbon\Carbon;

class Company
{
    public string $name;
    public Carbon $established;
    public float $latitude;
    public float $longitude;
}