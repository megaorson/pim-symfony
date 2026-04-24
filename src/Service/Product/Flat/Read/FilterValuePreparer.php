<?php
declare(strict_types=1);

namespace App\Service\Product\Flat\Read;

use App\Service\Product\Output\ProductAttributeValueCaster;

final readonly class FilterValuePreparer
{
    public function __construct(
        private ProductAttributeValueCaster $valueCaster,
    ) {}

    public function prepare(
        string $field,
        string $operator,
        mixed $rawValue,
        string $type,
    ): mixed {
        return match ($operator) {
            'IN' => $this->prepareInValues($rawValue, $type),
            'BEGINS' => (string) $rawValue . '%',
            default => $this->valueCaster->cast($rawValue, $type),
        };
    }

    /**
     * @return list<mixed>
     */
    private function prepareInValues(mixed $rawValue, string $type): array
    {
        $value = trim((string) $rawValue);

        if (str_starts_with($value, '(') && str_ends_with($value, ')')) {
            $value = substr($value, 1, -1);
        }

        $values = array_filter(
            array_map(
                static fn (string $item): string => trim($item),
                explode(',', $value)
            ),
            static fn (string $item): bool => $item !== ''
        );

        return array_map(
            fn (string $item): mixed => $this->valueCaster->cast($item, $type),
            array_values($values)
        );
    }
}
