<?php
declare(strict_types=1);

namespace App\Tests\Functional\Api\Product;

use App\Entity\Product;
use App\Entity\ProductAttributeValueImage;
use App\Tests\Functional\Support\ProductApiTestCase;
use App\Tests\Functional\Support\Trait\ProductImageTestTrait;

final class ProductDeleteTest extends ProductApiTestCase
{
    use ProductImageTestTrait;

    public function testDeleteExistingProduct(): void
    {
        $this->createDefaultProductAttributes();

        $product = $this->createProductThroughApi('SKU-DELETE-SIMPLE-1');

        $this->jsonDelete('/api/products/' . $product['id']);

        self::assertResponseStatusCodeSame(204);

        $this->entityManager->clear();

        $deletedProduct = $this->entityManager
            ->getRepository(Product::class)
            ->find($product['id']);

        self::assertNull($deletedProduct);
    }

    public function testDeleteMissingProductReturns404(): void
    {
        $this->jsonDelete('/api/products/999999');

        self::assertResponseStatusCodeSame(404);

        $response = $this->responseData(false);

        self::assertSame('product_not_found', $response['type']);
    }

    public function testDeleteProductRemovesAttributeValues(): void
    {
        $this->createProductAttribute(
            code: 'price_delete_test',
            type: 'decimal',
            name: 'Price Delete Test',
        );

        $this->createProductAttribute(
            code: 'qty_delete_test',
            type: 'int',
            name: 'Qty Delete Test',
        );

        $product = $this->createProductThroughApiByArray([
            'sku' => 'SKU-DELETE-WITH-VALUES-1',
            'attributes' => [
                'price_delete_test' => 199.99,
                'qty_delete_test' => 5,
            ],
        ]);

        $this->jsonDelete('/api/products/' . $product['id']);

        self::assertResponseStatusCodeSame(204);

        $this->entityManager->clear();

        $deletedProduct = $this->entityManager
            ->getRepository(Product::class)
            ->find($product['id']);

        self::assertNull($deletedProduct);
    }

    public function testDeleteProductRemovesImageValuesAndFiles(): void
    {
        $this->createDefaultProductAttributes();

        $attribute = $this->createProductAttribute(
            code: 'main_image',
            type: ProductAttributeValueImage::TYPE,
            name: 'Main Image',
        );

        $product = $this->createProductThroughApi('SKU-DELETE-PRODUCT-1');

        $file = $this->createUploadedFile('test-image.png', 'image/png');

        $result = $this->uploadProductImage($product['id'], $attribute->getCode(), $file);

        self::assertResponseStatusCodeSame(201);

        $uploadResponse = $result->toArray(false);
        $relativePath = $uploadResponse['image']['path'];
        $absolutePath = $this->projectDir . '/public/uploads/images/' . $relativePath;

        self::assertFileExists($absolutePath);

        $this->jsonDelete('/api/products/' . $product['id']);

        self::assertResponseStatusCodeSame(204);

        $deletedProduct = $this->entityManager
            ->getRepository(Product::class)
            ->find($product['id']);

        self::assertNull($deletedProduct);

        $imageValues = $this->entityManager
            ->getRepository(ProductAttributeValueImage::class)
            ->findBy([
                'product' => $product['id'],
            ]);

        self::assertCount(0, $imageValues);

        self::assertFileDoesNotExist($absolutePath);
    }

    public function testDeleteProductRemovesMultipleImageFiles(): void
    {
        $this->createDefaultProductAttributes();

        $mainImageAttribute = $this->createProductAttribute(
            code: 'main_image',
            type: ProductAttributeValueImage::TYPE,
            name: 'Main Image',
        );

        $galleryImageAttribute = $this->createProductAttribute(
            code: 'gallery_image',
            type: ProductAttributeValueImage::TYPE,
            name: 'Gallery Image',
        );

        $product = $this->createProductThroughApi('SKU-DELETE-PRODUCT-2');

        $file1 = $this->createUploadedFile('test-image.png', 'image/png');
        $result1 = $this->uploadProductImage($product['id'], $mainImageAttribute->getCode(), $file1);

        self::assertResponseStatusCodeSame(201);

        $response1 = $result1->toArray(false);
        $relativePath1 = $response1['image']['path'];
        $absolutePath1 = $this->projectDir . '/public/uploads/images/' . $relativePath1;

        self::assertFileExists($absolutePath1);

        $file2 = $this->createUploadedFile('test-image-2.png', 'image/png');
        $result2 = $this->uploadProductImage($product['id'], $galleryImageAttribute->getCode(), $file2);

        self::assertResponseStatusCodeSame(201);

        $response2 = $result2->toArray(false);
        $relativePath2 = $response2['image']['path'];
        $absolutePath2 = $this->projectDir . '/public/uploads/images/' . $relativePath2;

        self::assertFileExists($absolutePath2);

        $this->jsonDelete('/api/products/' . $product['id']);

        self::assertResponseStatusCodeSame(204);

        $this->entityManager->clear();

        $deletedProduct = $this->entityManager
            ->getRepository(Product::class)
            ->find($product['id']);

        self::assertNull($deletedProduct);

        $imageValues = $this->entityManager
            ->getRepository(ProductAttributeValueImage::class)
            ->findBy([
                'product' => $product['id'],
            ]);

        self::assertCount(0, $imageValues);

        self::assertFileDoesNotExist($absolutePath1);
        self::assertFileDoesNotExist($absolutePath2);
    }

    public function testDeleteProductDoesNotFailWhenImageFileAlreadyMissing(): void
    {
        $this->createDefaultProductAttributes();

        $attribute = $this->createProductAttribute(
            code: 'main_image',
            type: ProductAttributeValueImage::TYPE,
            name: 'Main Image',
        );

        $product = $this->createProductThroughApi('SKU-DELETE-PRODUCT-3');

        $file = $this->createUploadedFile('test-image.png', 'image/png');

        $result = $this->uploadProductImage($product['id'], $attribute->getCode(), $file);

        self::assertResponseStatusCodeSame(201);

        $uploadResponse = $result->toArray(false);
        $relativePath = $uploadResponse['image']['path'];
        $absolutePath = $this->projectDir . '/public/uploads/images/' . $relativePath;

        self::assertFileExists($absolutePath);

        unlink($absolutePath);

        self::assertFileDoesNotExist($absolutePath);

        $this->jsonDelete('/api/products/' . $product['id']);

        self::assertResponseStatusCodeSame(204);

        $this->entityManager->clear();

        $deletedProduct = $this->entityManager
            ->getRepository(Product::class)
            ->find($product['id']);

        self::assertNull($deletedProduct);

        $imageValues = $this->entityManager
            ->getRepository(ProductAttributeValueImage::class)
            ->findBy([
                'product' => $product['id'],
            ]);

        self::assertCount(0, $imageValues);
    }
}
