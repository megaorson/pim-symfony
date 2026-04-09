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
}
