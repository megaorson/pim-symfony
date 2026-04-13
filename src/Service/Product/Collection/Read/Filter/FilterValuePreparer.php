<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

use App\Exception\Api\InvalidFilterException;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class FilterValuePreparer
{
    public function __construct(
        private TranslatorInterface $translator,
        private BaseFieldTypeResolver $baseFieldTypeResolver,
    ) {
    }

    public function resolveBaseFieldType(string $field): string
    {
        return $this->baseFieldTypeResolver->resolve($field);
    }

    public function prepare(string $field, string $operator, mixed $rawValue, string $type): mixed
    {
        if (!is_string($rawValue)) {
            if ($operator === 'IN' && is_array($rawValue)) {
                return $rawValue;
            }

            return $rawValue;
        }

        if ($operator === 'IN') {
            $values = $this->parseInValues($rawValue, $type);

            if ($values === []) {
                throw new InvalidFilterException(
                    $this->translator->trans('eav.filter.empty_in_values', ['%field%' => $field])
                );
            }

            return $values;
        }

        $value = $this->normalizeScalarValue($rawValue, $type);

        if (in_array($operator, ['GT', 'GE', 'LT', 'LE'], true)) {
            $this->assertComparableScalarType($field, $operator, $type);
        }

        if ($operator === 'BEGINS') {
            return $this->escapeLike((string) $value) . '%';
        }

        return $value;
    }

    private function parseInValues(string $value, string $type): array
    {
        $value = trim($value);

        if (!str_starts_with($value, '(') || !str_ends_with($value, ')')) {
            throw new InvalidFilterException(
                $this->translator->trans('eav.filter.invalid_in_value', ['%value%' => $value])
            );
        }

        $inner = trim(substr($value, 1, -1));

        if ($inner === '') {
            return [];
        }

        $items = $this->splitInValues($inner);
        $result = [];

        foreach ($items as $item) {
            $item = trim($item);

            if ($item === '') {
                continue;
            }

            $result[] = $this->normalizeScalarValue($this->trimWrappingQuotes($item), $type);
        }

        return array_values($result);
    }

    private function splitInValues(string $input): array
    {
        $items = [];
        $buffer = '';
        $length = strlen($input);
        $inQuote = false;
        $quoteChar = null;

        for ($i = 0; $i < $length; $i++) {
            $char = $input[$i];

            if ($inQuote) {
                if ($char === '\\' && isset($input[$i + 1])) {
                    $buffer .= $input[$i + 1];
                    $i++;
                    continue;
                }

                if ($char === $quoteChar) {
                    $inQuote = false;
                    $quoteChar = null;
                }

                $buffer .= $char;
                continue;
            }

            if ($char === '\'' || $char === '"') {
                $inQuote = true;
                $quoteChar = $char;
                $buffer .= $char;
                continue;
            }

            if ($char === ',') {
                $items[] = $buffer;
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        if ($buffer !== '') {
            $items[] = $buffer;
        }

        return array_values($items);
    }

    private function normalizeScalarValue(string $value, string $type): int|float|string
    {
        $value = trim($value);

        return match ($type) {
            'int', 'boolean' => (int) $value,
            'decimal', 'float' => (float) $value,
            default => $this->trimWrappingQuotes($value),
        };
    }

    private function assertComparableScalarType(string $field, string $operator, string $type): void
    {
        if (!in_array($type, ['int', 'decimal', 'float', 'boolean'], true)) {
            throw new InvalidFilterException(
                $this->translator->trans('eav.filter.non_numeric_operator', [
                    '%operator%' => $operator,
                    '%field%' => $field,
                    '%type%' => $type,
                ])
            );
        }
    }

    private function trimWrappingQuotes(string $value): string
    {
        $value = trim($value);
        $length = strlen($value);

        if ($length >= 2) {
            $first = $value[0];
            $last = $value[$length - 1];

            if (($first === '\'' && $last === '\'') || ($first === '"' && $last === '"')) {
                $value = substr($value, 1, -1);
            }
        }

        return stripcslashes($value);
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
