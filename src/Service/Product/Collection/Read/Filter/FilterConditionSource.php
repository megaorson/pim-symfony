<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

final readonly class FilterConditionSource
{
    public function __construct(
        public string $sql,
        public array $parameters,
        public ?string $projectedColumn = null,
    ) {
    }
}
