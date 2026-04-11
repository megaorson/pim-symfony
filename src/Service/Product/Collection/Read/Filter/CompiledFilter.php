<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

final readonly class CompiledFilter
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        public string $sql,
        public array $parameters = [],
    ) {
    }

    public static function empty(): self
    {
        return new self('1=1', []);
    }

    public function isEmpty(): bool
    {
        return $this->sql === '1=1';
    }
}
