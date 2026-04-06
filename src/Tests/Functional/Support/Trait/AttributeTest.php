<?php
declare(strict_types=1);

namespace App\Tests\Functional\Support\Trait;

use App\Entity\ProductAttribute;

trait AttributeTest
{
    protected function validAttributePayload(array $overrides = []): array
    {
        static $counter = 1;

        $payload = [
            'code' => 'attr_' . $counter,
            'name' => 'Attribute ' . $counter,
            'type' => 'text',
            'isRequired' => false,
            'isFilterable' => false,
            'isSortable' => false,
            'isSelectable' => true,
        ];

        $counter++;

        return array_replace($payload, $overrides);
    }

    protected function validateAttributeEntity(array $attributeData = []): void
    {
        $attribute = $this->entityManager
            ->getRepository(ProductAttribute::class)
            ->findOneBy(['code' => $attributeData['code']]);

        self::assertNotNull($attribute);
        self::assertSame($attributeData['code'], $attribute->getCode());
        self::assertSame($attributeData['name'], $attribute->getName());
        self::assertSame($attributeData['isRequired'], $attribute->isRequired());
        self::assertSame($attributeData['isFilterable'], $attribute->isFilterable());
        self::assertSame($attributeData['isSortable'], $attribute->isSortable());
        self::assertSame($attributeData['isSelectable'], $attribute->isSelectable());
    }
}
