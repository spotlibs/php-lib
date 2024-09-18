<?php

declare(strict_types=1);

namespace Tests\Dtos;

use Carbon\Carbon;
use Laravel\Lumen\Testing\TestCase;

class DtosTest extends TestCase
{
    public function createApplication()
    {
        return require __DIR__.'/../../bootstrap/app.php';
    }

    /** @test */
    /** @runInSeparateProcess */
    public function testConstructDto()
    {
        $company = new Company();
        $company->name = "Google";
        $company->established = Carbon::parse("1998-09-04");
        $company->latitude = 37.44994307042218;
        $company->longitude = -122.1825452923763;
        $data = [
            'name' => 'Sergey',
            'age' => 33,
            'salary' => 100000.94,
            'gpa' => 3.98,
            'chores' => ['eat', 'sleep', 'coding'],
            'married' => true,
            'referal' => null,
            'company' => $company
        ];
        $dto = new Dto($data);
        $this->assertEquals('string', get_debug_type($dto->name));
        $this->assertEquals('int', get_debug_type($dto->age));
        $this->assertEquals('float', get_debug_type($dto->gpa));
        $this->assertEquals('float', get_debug_type($dto->salary));
        $this->assertEquals('null', get_debug_type($dto->referal));
        $arrDto = $dto->toArray();
        $this->assertEquals('array', get_debug_type($arrDto));
        $jsonDto = $dto->toJson();
        $this->assertEquals('string', get_debug_type($jsonDto));
    }

    /** @test */
    /** @runInSeparateProcess */
    public function testConstructDto2()
    {
        $company = new Company();
        $company->name = "Google";
        $company->established = Carbon::parse("1998-09-04");
        $company->latitude = 37.44994307042218;
        $company->longitude = -122.1825452923763;
        $data = [
            'name' => 'Sergey',
            'age' => 33,
            'salary' => 100000.94,
            'gpa' => 3.98,
            'chores' => ['eat', 'sleep', 'coding'],
            'married' => true,
            'referal' => 'Larry',
            'company' => $company,
            'dummy' => 'why am I still here?'
        ];
        $dto = Dto::create($data);
        $this->assertEquals('string', get_debug_type($dto->name));
        $this->assertEquals('int', get_debug_type($dto->age));
        $this->assertEquals('float', get_debug_type($dto->gpa));
        $this->assertEquals('float', get_debug_type($dto->salary));
    }

    /** @test */
    /** @runInSeparateProcess */
    public function testConstructDto3()
    {
        $company = new Company();
        $company->name = "Google";
        $company->established = Carbon::parse("1998-09-04");
        $company->latitude = 37.44994307042218;
        $company->longitude = -122.1825452923763;
        $vehicle = new Vehicle("BMW", "matic", 2000);
        $data = [
            'name' => 333,
            'age' => 'Sergey',
            'salary' => null,
            'gpa' => 'must be high',
            'chores' => null,
            'married' => 'perhaps',
            'referal' => 'Larry',
            'company' => $vehicle,
            'createdAt' => "2024-09-17",
            'dob' => Carbon::parse("1973-08-21")
        ];
        $dto = new Dto($data);
        $this->assertEquals('string', get_debug_type($dto->name));
        $this->assertEquals('int', get_debug_type($dto->age));
        $this->assertEquals('float', get_debug_type($dto->gpa));
        $this->assertEquals(0, $dto->gpa);
        $this->assertEquals('float', get_debug_type($dto->salary));
        $this->assertEquals('Carbon\Carbon', get_debug_type($dto->createdAt));
        $this->assertFalse($dto->married);
        $this->assertEquals("1973-08-21", $dto->dob);
    }
}