<?php
declare(strict_types=1);

namespace App\Service\Product\Flat\Read;

final readonly class SqlPredicateBuilder
{
    /**
     * @param mixed $preparedValue
     * @return array{0: string, 1: array<string, mixed>}
     */
    public function build(
        string $fieldExpression,
        string $operator,
        mixed $preparedValue,
        string $paramBase,
    ): array {
        return match ($operator) {
            'EQ' => [
                sprintf('%s = :%s', $fieldExpression, $paramBase),
                [$paramBase => $preparedValue],
            ],
            'NE' => [
                sprintf('(%s <> :%s OR %s IS NULL)', $fieldExpression, $paramBase, $fieldExpression),
                [$paramBase => $preparedValue],
            ],
            'GT' => [
                sprintf('%s > :%s', $fieldExpression, $paramBase),
                [$paramBase => $preparedValue],
            ],
            'GE' => [
                sprintf('%s >= :%s', $fieldExpression, $paramBase),
                [$paramBase => $preparedValue],
            ],
            'LT' => [
                sprintf('%s < :%s', $fieldExpression, $paramBase),
                [$paramBase => $preparedValue],
            ],
            'LE' => [
                sprintf('%s <= :%s', $fieldExpression, $paramBase),
                [$paramBase => $preparedValue],
            ],
            'IN' => [
                sprintf('%s IN (:%s)', $fieldExpression, $paramBase),
                [$paramBase => $preparedValue],
            ],
            'BEGINS' => [
                sprintf('%s LIKE :%s', $fieldExpression, $paramBase),
                [$paramBase => $preparedValue],
            ],
            default => throw new \RuntimeException(sprintf('Unsupported operator "%s".', $operator)),
        };
    }
}
