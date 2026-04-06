<?php
declare(strict_types=1);

namespace App\Tests\Functional\Api\Attribute;

use App\Entity\ProductAttribute;
use App\Tests\Functional\Support\ApiTestCase;

final class AttributeDeleteTest extends ApiTestCase
{
    public function testDeleteExistingAttribute(): void
    {
        $attribute = $this->createAttributeEntity(
            code: 'qty',
            name: 'Quantity',
            type: 'int',
        );

        $this->jsonDelete('/api/attributes/' . $attribute->getId());

        self::assertResponseStatusCodeSame(204);

        $this->entityManager->clear();

        $deleted = $this->entityManager
            ->getRepository(ProductAttribute::class)
            ->find((int)$attribute->getId());

        self::assertNull($deleted);
    }

    public function testDeleteMissingAttributeReturns404(): void
    {
        $this->jsonDelete('/api/attributes/999999');

        self::assertResponseStatusCodeSame(404);
    }
}
