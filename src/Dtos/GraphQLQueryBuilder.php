<?php

/**
 * PHP version 8
 *
 * @category Library
 * @package  Dtos
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @version  GIT: 0.0.4
 * @link     https://github.com/spotlibs
 */

declare(strict_types=1);

namespace Spotlibs\PhpLib\Dtos;

use Spotlibs\PhpLib\Exceptions\ParameterException;

/**
 * GraphQLQueryBuilder
 *
 * @category Library
 * @package  Dtos
 * @author   Mufthi Ryanda <mufthi.ryanda@icloud.com>
 * @license  https://mit-license.org/ MIT License
 * @link     https://github.com/spotlibs
 */

trait GraphQLQueryBuilder
{
    private array $selectedFields = [];
    abstract protected function getQueryFields(): array;
    abstract protected function getQueryName(): string;
    abstract protected function getOperationName(): string;

    public function select(string ...$fields): self
    {
        $queryFields = $this->getQueryFields();
        $invalidFields = array_diff($fields, $queryFields);

        if (!empty($invalidFields)) {
            throw new ParameterException(
                'Invalid field(s): ' . implode(', ', $invalidFields) .
                '. Available fields: ' . implode(', ', $queryFields)
            );
        }

        $this->selectedFields = array_merge($this->selectedFields, $fields);
        return $this;
    }

    public function toGraphQLQueryString(): string
    {
        $fields = empty($this->selectedFields)
            ? $this->getSelectedPropertiesAsFields()
            : $this->selectedFields;

        $indentedFields = array_map(fn($field) => "        {$field}", $fields);
        $fieldsList = implode("\n", $indentedFields);

        return sprintf(
            "query %s {\n    %s {\n%s\n    }\n}",
            $this->getQueryName(),
            $this->getOperationName(),
            $fieldsList
        );
    }

    private function getSelectedPropertiesAsFields(): array
    {
        return $this->getQueryFields();
    }
}