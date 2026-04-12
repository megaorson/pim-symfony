<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

use App\Exception\Api\InvalidFilterException;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class SqlPredicateBuilder
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param mixed $preparedValue
     * @return array{0:string,1:array<string,mixed>}
     */
    public function build(
        string $fieldExpression,
        string $operator,
        mixed $preparedValue,
        string $paramBase,
    ): array {
        return match ($operator) {
            'EQ' => [sprintf('%s = :%s', $fieldExpression, $paramBase), [$paramBase => $preparedValue]],
            'NE' => [sprintf('%s != :%s', $fieldExpression, $paramBase), [$paramBase => $preparedValue]],
            'GT' => [sprintf('%s > :%s', $fieldExpression, $paramBase), [$paramBase => $preparedValue]],
            'GE' => [sprintf('%s >= :%s', $fieldExpression, $paramBase), [$paramBase => $preparedValue]],
            'LT' => [sprintf('%s < :%s', $fieldExpression, $paramBase), [$paramBase => $preparedValue]],
            'LE' => [sprintf('%s <= :%s', $fieldExpression, $paramBase), [$paramBase => $preparedValue]],
            'BEGINS' => [sprintf('%s LIKE :%s', $fieldExpression, $paramBase), [$paramBase => $preparedValue]],
            'IN' => $this->compileInPredicate($fieldExpression, $preparedValue, $paramBase),
            default => throw new InvalidFilterException(
                $this->translator->trans('eav.filter.unsupported_operator', ['%operator%' => $operator])
            ),
        };
    }

    /**
     * @param mixed $value
     * @return array{0:string,1:array<string,mixed>}
     */
    private function compileInPredicate(string $fieldExpression, mixed $value, string $paramBase): array
    {
        if (!is_array($value) || $value === []) {
            throw new InvalidFilterException(
                $this->translator->trans('eav.filter.invalid_in_value', [
                    '%value%' => is_scalar($value) ? (string) $value : get_debug_type($value),
                ])
            );
        }

        $placeholders = [];
        $parameters = [];

        foreach (array_values($value) as $i => $item) {
            $param = $paramBase . '_' . $i;
            $placeholders[] = ':' . $param;
            $parameters[$param] = $item;
        }

        return [
            sprintf('%s IN (%s)', $fieldExpression, implode(', ', $placeholders)),
            $parameters,
        ];
    }
}
