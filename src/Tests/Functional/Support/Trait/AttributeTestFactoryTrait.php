<?php
declare(strict_types=1);

namespace App\Tests\Functional\Support\Trait;

use App\Entity\ProductAttribute;

trait AttributeTestFactoryTrait
{
    protected function createAttributeEntity(
        ?string $code = null,
        ?string $name = null,
        string $type = 'text',
        bool $isRequired = false,
        bool $isFilterable = false,
        bool $isSortable = false,
        bool $isSelectable = true,
    ): ProductAttribute {
        static $counter = 1;

        $code ??= 'attr_' . $counter;
        $name ??= 'Attribute ' . $counter;
        $counter++;

        $attribute = new ProductAttribute();

        $attribute->setCode($code);
        $attribute->setName($name);
        $attribute->setType($type);

        $attribute->setIsRequired($isRequired);
        $attribute->setIsFilterable($isFilterable);
        $attribute->setIsSortable($isSortable);
        $attribute->setIsSelectable($isSelectable);

        $this->entityManager->persist($attribute);
        $this->entityManager->flush();

        return $attribute;
    }
}
