<?php
declare(strict_types=1);

namespace App\Tests\Functional\Support\Trait;

use App\Entity\Product;
use App\Repository\ProductRepository;
use PHPUnit\Framework\Assert;

trait ProductTestTrait
{
    protected function getProductRepository(): ProductRepository
    {
        /** @var ProductRepository $repository */
        $repository = $this->entityManager->getRepository(Product::class);

        return $repository;
    }

    protected function getProductById(int $id): Product
    {
        $product = $this->getProductRepository()->find($id);

        Assert::assertNotNull(
            $product,
            sprintf('Product with id %d was not found.', $id)
        );

        return $product;
    }

    protected function getProductBySku(string $sku): Product
    {
        $product = $this->getProductRepository()->findOneBy(['sku' => $sku]);

        Assert::assertNotNull(
            $product,
            sprintf('Product with sku "%s" was not found.', $sku)
        );

        return $product;
    }

    protected function assertProductResponseShape(array $data): void
    {
        Assert::assertArrayHasKey('id', $data);
        Assert::assertArrayHasKey('attributes', $data);

        Assert::assertIsInt($data['id']);
        Assert::assertIsArray($data['attributes']);
    }

    protected function assertProductResponseContains(
        array $data,
        string $expectedSku,
        array $expectedAttributes = []
    ): void {
        $this->assertProductResponseShape($data);

        Assert::assertArrayHasKey('sku', $data['attributes']);
        Assert::assertSame($expectedSku, $data['attributes']['sku']);

        foreach ($expectedAttributes as $attributeCode => $expectedValue) {
            Assert::assertArrayHasKey($attributeCode, $data['attributes']);
            Assert::assertSame($expectedValue, $data['attributes'][$attributeCode]);
        }
    }

    protected function assertProductResponseDoesNotContainAttribute(
        array $data,
        string $attributeCode
    ): void {
        Assert::assertArrayHasKey('attributes', $data);
        Assert::assertArrayNotHasKey($attributeCode, $data['attributes']);
    }
}
