<?php

declare(strict_types=1);

namespace Tests\Dtos;

use Carbon\Carbon;
use Exception;
use Laravel\Lumen\Testing\TestCase;
use TypeError;

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
            'company' => $company,
            'createdAt' => Carbon::parse("2024-06-18")
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
        $this->assertEquals('2024-06-18 00:00:00', $arrDto['createdAt']);
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
        $this->assertEquals("1973-08-21 00:00:00", $dto->dob);
    }

    /** @test */
    /** @runInSeparateProcess */
    public function testConstructDto4()
    {
        $vehicle = new Vehicle("BMW", "matic", 2000);
        $data = [
            'name' => 'andrew',
            'employeeId' => 1,
            'isActive' => true,
            'relatives' => ['robert', 'lana', 'garry'],
            'vehicle' => $vehicle
        ];
        $dto = new Dto2($data);
        $this->assertEquals('string', get_debug_type($dto->name));
        $this->assertEquals($dto->name, 'andrew');
        $arrDto = $dto->toArray();
        $jsonDto = $dto->toJson();
        $this->assertEquals('array', get_debug_type($arrDto));
        $this->assertEquals('string', get_debug_type($jsonDto));
    }

    /** @test */
    /** @runInSeparateProcess */
    public function testConstructDto5()
    {
        $vehicle = new Vehicle("BMW", "matic", 2000);
        $data = [
            'name' => 'andrew',
            'employeeId' => 1,
            'isActive' => true,
            'relatives' => ['robert', 'lana', 'garry'],
            'vehicle' => $vehicle
        ];
        $dto = Dto2::create($data);
        $this->assertEquals('string', get_debug_type($dto->name));
        $this->assertEquals($dto->name, 'andrew');
    }

    /** @test */
    /** @runInSeparateProcess */
    public function testConstructDtoError()
    {
        $this->expectException(TypeError::class);
        $vehicle = new Vehicle("BMW", "matic", 2000);
        $data = [
            'name' => 123,
            'employeeId' => 1,
            'isActive' => true,
            'relatives' => ['robert', 'lana', 'garry'],
            'vehicle' => $vehicle
        ];
        new Dto2($data);
    }

    /** @test */
    /** @runInSeparateProcess */
    public function testDtoNestedArray(): void
    {
        $data = [
            'name' => 'Bruce',
            'employeeId' => 1,
            'isActive' => true,
            'friend' => 'Yuri',
            'relatives' => ['robert', 'lana', 'garry'],
            'partner' => [
                'name' => 'Amanda',
                'age' => 24,
                'dob' => '2000-10-31',
                'is_alive' => true,
                'dog' => [
                    'name' => 'Joshua',
                    'age' => 3
                ]
            ],
            'siblings' => [
                [
                    'name' => 'Jacob',
                    'age' => 16,
                    'dob' => '2000-10-31',
                    'is_alive' => true
                ],
                [
                    'name' => 'Tony',
                    'age' => 35,
                    'dob' => '2000-10-31',
                    'is_alive' => true
                ],
            ]
        ];
        $x = new Dto2($data);
        $this->assertEquals('Amanda', $x->partner->name);
        $this->assertEquals('Joshua', $x->partner->dog->name);
        $this->assertEquals('Tony', $x->siblings[1]->name);
        $this->assertEquals(16, $x->siblings[0]->age);
    }

    /** @test */
    /** @runInSeparateProcess */
    public function testDtoWithoutArrObjMap(): void
    {
        $data = [
            'name' => 'Johan',
            'age' => 29,
            'is_married' => true
        ];
        $x = new Dto3($data);
        $this->assertEquals('Johan', $x->name);
    }
}