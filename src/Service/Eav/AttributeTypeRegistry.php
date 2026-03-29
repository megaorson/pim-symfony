<?php
declare(strict_types=1);

namespace App\Service\Eav;

use App\Entity\ProductAttributeTypeInterface;

final class AttributeTypeRegistry
{
    /**
     * @param array<string, class-string> $valueEntityMap
     */
    public function __construct(
        private readonly array $valueEntityMap
    ) {
    }

    public function getValueEntityClass(string $type): string
    {
        if (!isset($this->valueEntityMap[$type])) {
            throw new \InvalidArgumentException(sprintf(
                'Unsupported attribute type "%s". Configure it in "app.eav_value_entity_map".',
                $type
            ));
        }

        return $this->valueEntityMap[$type];
    }

    public function create(string $attributeType)
    : ProductAttributeTypeInterface {
        $class = $this->getValueEntityClass($attributeType);
        return new $class();
    }

    /**
     * @return array<string, class-string>
     */
    public function all(): array
    {
        return $this->valueEntityMap;
    }
}
