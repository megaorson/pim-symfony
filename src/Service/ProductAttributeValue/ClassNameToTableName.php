<?php
declare(strict_types=1);

namespace App\Service\ProductAttributeValue;

final class ClassNameToTableName
{
    public function execute(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);
        $shortName = end($parts);

        if (!is_string($shortName) || $shortName === '') {
            throw new \RuntimeException(sprintf('Cannot resolve short class name from "%s".', $fqcn));
        }

        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $shortName));
    }
}
