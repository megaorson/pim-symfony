<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

final readonly class OptimizedFilterSource
{
    /**
     * @param array<string, mixed> $parameters
     * @param array<string, string> $projectedColumnsByField
     */
    public function __construct(
        public string $sql,
        public array $parameters,
        public array $projectedColumnsByField = [],
    ) {
    }

    public function hasProjectedField(string $field): bool
    {
        return isset($this->projectedColumnsByField[$field]);
    }

    public function getProjectedColumn(string $field): string
    {
        return $this->projectedColumnsByField[$field];
    }
}
