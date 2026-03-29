<?php
declare(strict_types=1);

namespace App\Service\Eav;

final class FilterDocumentationBuilder
{
    public function __construct(
        private readonly FilterFieldProvider $fieldProvider,
        private readonly FilterExampleGenerator $exampleGenerator
    ) {
    }

    public function buildFilterDescription(): string
    {
        $fields = $this->fieldProvider->getFilterableCodes();

        return sprintf(
            'Filter expression format: <field> <operator> <value>. Supported operators: EQ, NE, GT, GE, LT, LE, IN, BEGINS. Multiple expressions can be combined with AND / OR and parentheses. Available filter fields: %s',
            implode(', ', $fields)
        );
    }

    public function buildFilterExample(): string
    {
        return $this->exampleGenerator->generateExample();
    }

    public function buildSelectDescription(): string
    {
        $fields = $this->fieldProvider->getSelectableCodes();

        return sprintf(
            'Comma separated list of attributes to include in response. Use "*" to include all selectable attributes. Available select fields: %s',
            implode(', ', $fields)
        );
    }

    public function buildSelectExample(): string
    {
        return 'sku,name,price';
    }
}
