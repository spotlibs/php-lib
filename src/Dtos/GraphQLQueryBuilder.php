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

    /**
     * Get available query fields
     *
     * @return array Array of available field names
     */
    abstract protected function getQueryFields(): array;

    /**
     * Get the GraphQL query name
     *
     * @return string The query name
     */
    abstract protected function getQueryName(): string;

    /**
     * Get the GraphQL operation name
     *
     * @return string The operation name
     */
    abstract protected function getOperationName(): string;

    /**
     * Select specific fields for the GraphQL query
     *
     * @param string ...$fields The field names to select
     *
     * @return self Returns the current instance for method chaining
     *
     * @throws ParameterException When invalid fields are provided
     */
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

    /**
     * Convert the query to a GraphQL query string
     *
     * @return string The formatted GraphQL query string
     */
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

    /**
     * Get selected properties as GraphQL fields
     *
     * @return array Array of field names
     */
    private function getSelectedPropertiesAsFields(): array
    {
        return $this->getQueryFields();
    }
}
