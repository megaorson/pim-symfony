<?php
declare(strict_types=1);

namespace App\Tests\Functional\Api\Attribute;

use App\Entity\ProductAttribute;
use App\Tests\Functional\Support\ApiTestCase;

final class AttributePatchTest extends ApiTestCase
{
    public function testPatchUpdatesNameAndFlags(): void
    {
        $attribute = $this->createAttributeEntity(
            code: 'name',
            name: 'Name',
        );

        $created = $this->entityManager
            ->getRepository(ProductAttribute::class)
            ->find($attribute->getId());

        self::assertNotNull($created);

        $this->jsonPatch('/api/attributes/' . $attribute->getId(), [
            'name' => 'Product Name',
            'isFilterable' => true,
            'isSortable' => true,
            'isSelectable' => false,
        ]);

        self::assertResponseStatusCodeSame(200);
        $this->assertResponseIsJson();

        $data = $this->responseData();

        self::assertSame('name', $data['code']);
        self::assertSame('Product Name', $data['name']);
        self::assertSame('text', $data['type']);
        self::assertFalse($data['isRequired']);
        self::assertTrue($data['isFilterable']);
        self::assertTrue($data['isSortable']);
        self::assertFalse($data['isSelectable']);

        $this->entityManager->clear();

        $updated = $this->entityManager
            ->getRepository(ProductAttribute::class)
            ->find($attribute->getId());

        self::assertNotNull($updated);
        self::assertSame('Product Name', $updated->getName());
    }

    public function testPatchMissingAttributeReturns404(): void
    {
        $this->jsonPatch('/api/attributes/999999', [
            'name' => 'Ghost',
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testPatchDoesNotOverwriteUnspecifiedFields(): void
    {
        $attribute = $this->createAttributeEntity(
            code: 'price',
            name: 'Price',
            type: 'decimal',
            isRequired: true,
            isFilterable: true,
            isSortable: true,
            isSelectable: false,
        );

        $this->jsonPatch('/api/attributes/' . $attribute->getId(), [
            'name' => 'Product Price',
        ]);

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();

        self::assertSame('price', $data['code']);
        self::assertSame('Product Price', $data['name']);
        self::assertSame('decimal', $data['type']);
        self::assertTrue($data['isRequired']);
        self::assertTrue($data['isFilterable']);
        self::assertTrue($data['isSortable']);
        self::assertFalse($data['isSelectable']);
    }

    public function testPatchThrowErrorOnNotExistsFields(): void
    {
        $attribute = $this->createAttributeEntity(
            code: 'price',
            name: 'Price',
            type: 'decimal',
            isRequired: true,
            isFilterable: true,
            isSortable: true,
            isSelectable: false,
        );

        $this->jsonPatch('/api/attributes/' . $attribute->getId(), [
            'name_test' => 'Product Price Test',
        ]);

        self::assertResponseStatusCodeSame(422);
        $this->assertResponseIsProblemJson();
        $this->assertViolationFor('name');

        $this->entityManager->clear();

        $created = $this->entityManager
            ->getRepository(ProductAttribute::class)
            ->find($attribute->getId());

        self::assertSame('price', $created->getCode());
        self::assertSame('Price', $created->getName());
        self::assertSame('decimal', $created->getType());
        self::assertTrue($created->isRequired());
        self::assertTrue($created->isFilterable());
        self::assertTrue($created->isSortable());
        self::assertFalse($created->isSelectable());
    }
}
