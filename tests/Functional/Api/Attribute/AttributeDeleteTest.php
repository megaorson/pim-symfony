<?php
declare(strict_types=1);

namespace App\Tests\Functional\Api\Attribute;

use App\Entity\ProductAttribute;
use App\Entity\ProductAttributeValueDecimal;
use App\Entity\ProductAttributeValueImage;
use App\Entity\ProductAttributeValueInt;
use App\Entity\ProductAttributeValueText;
use App\Tests\Functional\Support\AttributeApiTestCase;
use App\Tests\Functional\Support\Trait\ProductImageTestTrait;
use App\Tests\Functional\Support\Trait\ProductTestFactoryTrait;
use App\Tests\Functional\Support\Trait\ProductTestTrait;

final class AttributeDeleteTest extends AttributeApiTestCase
{
    use ProductTestFactoryTrait;
    use ProductTestTrait;
    use ProductImageTestTrait;

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
            ->find((int) $attribute->getId());

        self::assertNull($deleted);
    }

    public function testDeleteMissingAttributeReturns404(): void
    {
        $this->jsonDelete('/api/attributes/999999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testDeleteAttributeFailsWhenDecimalAttributeHasValues(): void
    {
        $attribute = $this->createProductAttribute(
            code: 'price',
            type: 'decimal',
            name: 'Price',
        );

        $product = $this->createProductThroughApiByArray([
            'sku' => 'SKU-DELETE-ATTR-1',
            'attributes' => [
                'price' => 199.99,
            ],
        ]);

        self::assertNotEmpty($product['id']);

        $this->jsonDelete('/api/attributes/' . $attribute->getId());

        self::assertResponseStatusCodeSame(409);

        $response = $this->responseData(false);

        self::assertSame('product_attribute_in_use', $response['type']);

        $existingAttribute = $this->entityManager
            ->getRepository(ProductAttribute::class)
            ->find($attribute->getId());

        self::assertNotNull($existingAttribute);

        $existingValues = $this->entityManager
            ->getRepository(ProductAttributeValueDecimal::class)
            ->findBy([
                'attribute' => $attribute->getId(),
            ]);

        self::assertNotEmpty($existingValues);
    }

    public function testDeleteAttributeFailsWhenIntAttributeHasValues(): void
    {
        $attribute = $this->createProductAttribute(
            code: 'qty',
            type: 'int',
            name: 'Quantity',
        );

        $product = $this->createProductThroughApiByArray([
            'sku' => 'SKU-DELETE-ATTR-INT-1',
            'attributes' => [
                'qty' => 10,
            ],
        ]);

        self::assertNotEmpty($product['id']);

        $this->jsonDelete('/api/attributes/' . $attribute->getId());

        self::assertResponseStatusCodeSame(409);

        $response = $this->responseData(false);

        self::assertSame('product_attribute_in_use', $response['type']);

        $existingAttribute = $this->entityManager
            ->getRepository(ProductAttribute::class)
            ->find($attribute->getId());

        self::assertNotNull($existingAttribute);

        $existingValues = $this->entityManager
            ->getRepository(ProductAttributeValueInt::class)
            ->findBy([
                'attribute' => $attribute->getId(),
            ]);

        self::assertNotEmpty($existingValues);
    }

    public function testDeleteAttributeFailsWhenTextAttributeHasValues(): void
    {
        $attribute = $this->createProductAttribute(
            code: 'name',
            type: 'text',
            name: 'Name',
        );

        $product = $this->createProductThroughApiByArray([
            'sku' => 'SKU-DELETE-ATTR-TEXT-1',
            'attributes' => [
                'name' => 'Test product',
            ],
        ]);

        self::assertNotEmpty($product['id']);

        $this->jsonDelete('/api/attributes/' . $attribute->getId());

        self::assertResponseStatusCodeSame(409);

        $response = $this->responseData(false);

        self::assertSame('product_attribute_in_use', $response['type']);

        $existingAttribute = $this->entityManager
            ->getRepository(ProductAttribute::class)
            ->find($attribute->getId());

        self::assertNotNull($existingAttribute);

        $existingValues = $this->entityManager
            ->getRepository(ProductAttributeValueText::class)
            ->findBy([
                'attribute' => $attribute->getId(),
            ]);

        self::assertNotEmpty($existingValues);
    }

    public function testDeleteAttributeFailsWhenImageAttributeHasValues(): void
    {
        $attribute = $this->createProductAttribute(
            code: 'main_image',
            type: ProductAttributeValueImage::TYPE,
            name: 'Main Image',
        );

        $product = $this->createProductThroughApiByArray([
            'sku' => 'SKU-DELETE-ATTR-IMG-1',
            'attributes' => [],
        ]);

        $file = $this->createUploadedFile('test-image.png', 'image/png');

        $this->uploadProductImage($product['id'], $attribute->getCode(), $file);

        self::assertResponseStatusCodeSame(201);

        $this->jsonDelete('/api/attributes/' . $attribute->getId());

        self::assertResponseStatusCodeSame(409);

        $response = $this->responseData(false);

        self::assertSame('product_attribute_in_use', $response['type']);

        $existingAttribute = $this->entityManager
            ->getRepository(ProductAttribute::class)
            ->find($attribute->getId());

        self::assertNotNull($existingAttribute);

        $existingValues = $this->entityManager
            ->getRepository(ProductAttributeValueImage::class)
            ->findBy([
                'attribute' => $attribute->getId(),
            ]);

        self::assertNotEmpty($existingValues);
    }

    public function testDeleteAttributeSucceedsWhenProductsExistButAttributeHasNoValues(): void
    {
        $attribute = $this->createProductAttribute(
            code: 'description',
            type: 'text',
            name: 'Description',
        );

        $product = $this->createProductThroughApiByArray([
            'sku' => 'SKU-DELETE-ATTR-NO-VALUES-1',
            'attributes' => [],
        ]);

        self::assertNotEmpty($product['id']);

        $this->jsonDelete('/api/attributes/' . $attribute->getId());

        self::assertResponseStatusCodeSame(204);

        $this->entityManager->clear();

        $deleted = $this->entityManager
            ->getRepository(ProductAttribute::class)
            ->find($attribute->getId());

        self::assertNull($deleted);
    }
}
