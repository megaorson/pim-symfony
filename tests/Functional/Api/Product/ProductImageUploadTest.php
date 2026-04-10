<?php
declare(strict_types=1);

namespace App\Tests\Functional\Api\Product;

use App\Entity\ProductAttributeValueImage;
use App\Tests\Functional\Support\ApiTestCase;
use App\Tests\Functional\Support\Trait\AttributeTestFactoryTrait;
use App\Tests\Functional\Support\Trait\ProductImageTestTrait;
use App\Tests\Functional\Support\Trait\ProductTestFactoryTrait;

final class ProductImageUploadTest extends ApiTestCase
{
    use ProductTestFactoryTrait;
    use AttributeTestFactoryTrait;
    use ProductImageTestTrait;

    public function testUploadImageSuccess(): void
    {
        $this->createDefaultProductAttributes();

        $attribute = $this->createProductAttribute(
            code: 'main_image',
            type: ProductAttributeValueImage::TYPE,
            name: 'Main Image',
        );

        $product = $this->createProductThroughApi('SKU-IMG-1');

        $file = $this->createUploadedFile('test-image.png', 'image/png');

        $this->uploadProductImage($product['id'], $attribute->getCode(), $file);

        self::assertResponseStatusCodeSame(201);

        $response = $this->responseData();

        self::assertArrayHasKey('message', $response);
        self::assertArrayHasKey('productId', $response);
        self::assertArrayHasKey('attributeCode', $response);
        self::assertArrayHasKey('image', $response);
        self::assertArrayHasKey('path', $response['image']);
        self::assertArrayHasKey('url', $response['image']);

        self::assertSame($product['id'], $response['productId']);
        self::assertSame($attribute->getCode(), $response['attributeCode']);

        $relativePath = $response['image']['path'];

        self::assertStringContainsString(
            sprintf('products/%d/%d_%s/', $product['id'], $attribute->getId(), $attribute->getCode()),
            $relativePath
        );

        $absolutePath = $this->projectDir . '/public/uploads/images/' . $relativePath;

        self::assertFileExists($absolutePath);
    }

    public function testUploadFailsWhenAttributeNotFound(): void
    {
        $this->createDefaultProductAttributes();

        $product = $this->createProductThroughApi('SKU-IMG-2');
        $file = $this->createUploadedFile('test-image.png', 'image/png');

        $responseData = $this->uploadProductImage($product['id'], 'unknown', $file);

        self::assertResponseStatusCodeSame(404);
        $response = $responseData->toArray(false);

        self::assertSame('product_attribute_not_found', $response['type']);
    }

    public function testUploadFailsWhenProductNotFound(): void
    {
        $this->createDefaultProductAttributes();

        $attribute = $this->createProductAttribute(
            code: 'main_image',
            type: ProductAttributeValueImage::TYPE,
            name: 'Main Image',
        );

        $file = $this->createUploadedFile('test-image.png', 'image/png');

        $result = $this->uploadProductImage(999999, $attribute->getCode(), $file);

        self::assertResponseStatusCodeSame(404);

        $response = $result->toArray(false);

        self::assertSame('product_not_found', $response['type']);
    }

    public function testUploadFailsWhenAttributeIsNotImage(): void
    {
        $this->createDefaultProductAttributes();

        $attribute = $this->createProductAttribute(
            code: 'title',
            type: 'text',
            name: 'Title',
        );

        $product = $this->createProductThroughApi('SKU-IMG-3');
        $file = $this->createUploadedFile('test-image.png', 'image/png');

        $result = $this->uploadProductImage($product['id'], $attribute->getCode(), $file);

        self::assertResponseStatusCodeSame(400);

        $response = $result->toArray(false);

        self::assertSame('invalid_product_attribute_type', $response['type']);
    }

    public function testUploadFailsWhenImageFileIsEmpty(): void
    {
        $this->createDefaultProductAttributes();

        $attribute = $this->createProductAttribute(
            code: 'main_image',
            type: ProductAttributeValueImage::TYPE,
            name: 'Main Image',
        );

        $product = $this->createProductThroughApi('SKU-IMG-EMPTY-1');

        $file = $this->createUploadedFile('empty.png', 'image/png');

        $result = $this->uploadProductImage($product['id'], $attribute->getCode(), $file);

        self::assertResponseStatusCodeSame(400);

        $response = $result->toArray(false);

        self::assertSame('invalid_product_image_mime_type', $response['type']);
    }

    public function testUploadFailsWhenMimeInvalid(): void
    {
        $this->createDefaultProductAttributes();

        $attribute = $this->createProductAttribute(
            code: 'main_image',
            type: ProductAttributeValueImage::TYPE,
            name: 'Main Image',
        );

        $product = $this->createProductThroughApi('SKU-IMG-4');
        $file = $this->createUploadedFile('test-file.txt', 'text/plain');

        $result = $this->uploadProductImage($product['id'], $attribute->getCode(), $file);

        self::assertResponseStatusCodeSame(400);

        $response = $result->toArray(false);

        self::assertSame('invalid_product_image_mime_type', $response['type']);
    }

    public function testUploadReplacesExistingImage(): void
    {
        $this->createDefaultProductAttributes();

        $attribute = $this->createProductAttribute(
            code: 'main_image',
            type: ProductAttributeValueImage::TYPE,
            name: 'Main Image',
        );

        $product = $this->createProductThroughApi('SKU-IMG-5');

        $file1 = $this->createUploadedFile('test-image.png', 'image/png');
        $result = $this->uploadProductImage($product['id'], $attribute->getCode(), $file1);

        self::assertResponseStatusCodeSame(201);

        $firstResponse = $result->toArray(false);
        $oldRelativePath = $firstResponse['image']['path'];
        $oldAbsolutePath = $this->projectDir . '/public/uploads/images/' . $oldRelativePath;

        self::assertFileExists($oldAbsolutePath);

        $this->flushAndClear();

        $file2 = $this->createUploadedFile('test-image-2.png', 'image/png');
        $result = $this->uploadProductImage($product['id'], $attribute->getCode(), $file2);

        self::assertResponseStatusCodeSame(201);

        $secondResponse = $result->toArray(false);
        $newRelativePath = $secondResponse['image']['path'];
        $newAbsolutePath = $this->projectDir . '/public/uploads/images/' . $newRelativePath;

        self::assertNotSame($oldRelativePath, $newRelativePath);
        self::assertFileExists($newAbsolutePath);
        self::assertFileDoesNotExist($oldAbsolutePath);

        $this->flushAndClear();

        $values = $this->entityManager
            ->getRepository(ProductAttributeValueImage::class)
            ->findBy([
                'product' => $product['id'],
                'attribute' => $attribute->getId(),
            ]);

        self::assertCount(1, $values);
    }
}
