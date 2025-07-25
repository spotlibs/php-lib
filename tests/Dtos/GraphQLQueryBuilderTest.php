<?php

declare(strict_types=1);

namespace Tests\Dtos;

use Laravel\Lumen\Testing\TestCase;
use Spotlibs\PhpLib\Exceptions\ParameterException;

class GraphQLQueryBuilderTest extends TestCase
{
    public function createApplication()
    {
        return require __DIR__.'/../../bootstrap/app.php';
    }

    /** @test */
    /** @runInSeparateProcess */
    public function testSelectValidFields()
    {
        $query = new MockGraphQLQuery();
        $result = $query->select('name', 'email');

        $this->assertEquals($query, $result);
        $this->assertEquals(['name', 'email'], $query->getSelectedFields());
    }

    /** @test */
    /** @runInSeparateProcess */
    public function testSelectMultipleCallsAppendFields()
    {
        $query = new MockGraphQLQuery();
        $query->select('name')
            ->select('email', 'age');

        $this->assertEquals(['name', 'email', 'age'], $query->getSelectedFields());
    }

    /** @test */
    /** @runInSeparateProcess */
    public function testSelectInvalidFieldsThrowsException()
    {
        $this->expectException(ParameterException::class);
        $this->expectExceptionMessage('Invalid field(s): invalid, unknown. Available fields: name, email, age, active');

        $query = new MockGraphQLQuery();
        $query->select('name', 'invalid', 'unknown');
    }

    /** @test */
    /** @runInSeparateProcess */
    public function testToGraphQLQueryStringWithSelectedFields()
    {
        $query = new MockGraphQLQuery();
        $query->select('name', 'email');
        $result = $query->toGraphQLQueryString();

        $expected = "query TestQuery {\n    testOperation {\n        name\n        email\n    }\n}";
        $this->assertEquals($expected, $result);
    }

    /** @test */
    /** @runInSeparateProcess */
    public function testToGraphQLQueryStringWithoutSelectedFields()
    {
        $query = new MockGraphQLQuery();
        $result = $query->toGraphQLQueryString();

        $expected = "query TestQuery {\n    testOperation {\n        name\n        email\n        age\n        active\n    }\n}";
        $this->assertEquals($expected, $result);
    }

    /** @test */
    /** @runInSeparateProcess */
    public function testToGraphQLQueryStringFormat()
    {
        $query = new MockGraphQLQuery();
        $query->select('name');
        $result = $query->toGraphQLQueryString();

        $this->assertStringContainsString('query TestQuery', $result);
        $this->assertStringContainsString('testOperation', $result);
        $this->assertStringContainsString('        name', $result);
    }

    /** @test */
    /** @runInSeparateProcess */
    public function testSelectEmptyFieldsArray()
    {
        $query = new MockGraphQLQuery();
        $result = $query->select();

        $this->assertEquals($query, $result);
        $this->assertEquals([], $query->getSelectedFields());
    }
}