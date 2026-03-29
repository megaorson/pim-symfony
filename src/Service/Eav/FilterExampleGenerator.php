<?php
declare(strict_types=1);

namespace App\Service\Eav;

final class FilterExampleGenerator
{
    public function generateExample(): string
    {
        return "(price GT 1000 OR sku BEGINS 'test') AND name EQ 'Test Product'";
    }
}
