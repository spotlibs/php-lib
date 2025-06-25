<?php

declare(strict_types=1);

namespace Tests\Dtos;

use Spotlibs\PhpLib\Dtos\GraphQLQueryBuilder;

class MockGraphQLQuery
{
    use GraphQLQueryBuilder;

    protected function getQueryFields(): array
    {
        return ['name', 'email', 'age', 'active'];
    }

    protected function getQueryName(): string
    {
        return 'TestQuery';
    }

    protected function getOperationName(): string
    {
        return 'testOperation';
    }

    public function getSelectedFields(): array
    {
        return $this->selectedFields;
    }
}