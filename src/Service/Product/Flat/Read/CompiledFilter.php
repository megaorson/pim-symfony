<?php
declare(strict_types=1);

namespace App\Service\Product\Flat\Read;

final readonly class CompiledFilter
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        public string $sql,
        public array $parameters,
    ) {
    }
}
