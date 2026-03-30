<?php
declare(strict_types=1);

namespace App\ApiResource\Dto;

final class ProductOutput
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public int $id,
        public array $attributes,
    ) {
    }
}
