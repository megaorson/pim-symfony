<?php
declare(strict_types=1);

namespace App\Tests\Functional\Support\Trait;

use App\Entity\Product;
use App\Entity\ProductAttribute;
use App\Entity\ProductAttributeValueDecimal;
use App\Entity\ProductAttributeValueImage;
use App\Entity\ProductAttributeValueInt;
use App\Entity\ProductAttributeValueText;

trait ProductTestFactoryTrait
{
    protected function validProductPayload(array $overrides = []): array
    {
        static $counter = 1;

        $payload = [
            'sku' => 'sku-' . $counter,
            'attributes' => [],
        ];

        $counter++;

        return array_replace_recursive($payload, $overrides);
    }

    protected function createProductEntity(?string $sku = null): Product
    {
        static $counter = 1;

        $sku ??= 'sku-' . $counter;
        $counter++;

        $product = new Product();
        $product->setSku($sku);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    protected function attachTextValue(Product $product, ProductAttribute $attribute, string $value): void
    {
        $entity = new ProductAttributeValueText();
        $entity->setProduct($product);
        $entity->setAttribute($attribute);
        $entity->setValue($value);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    protected function attachIntValue(Product $product, ProductAttribute $attribute, int $value): void
    {
        $entity = new ProductAttributeValueInt();
        $entity->setProduct($product);
        $entity->setAttribute($attribute);
        $entity->setValue($value);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    protected function attachDecimalValue(Product $product, ProductAttribute $attribute, float $value): void
    {
        $entity = new ProductAttributeValueDecimal();
        $entity->setProduct($product);
        $entity->setAttribute($attribute);
        $entity->setValue($value);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    protected function attachImageValue(Product $product, ProductAttribute $attribute, string $value): void
    {
        $entity = new ProductAttributeValueImage();
        $entity->setProduct($product);
        $entity->setAttribute($attribute);
        $entity->setValue($value);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    /**
     * Удобный helper для быстрого наполнения продукта атрибутами.
     *
     * Ожидает map вида:
     * [
     *   'name' => 'Phone',
     *   'price' => 1999.99,
     *   'qty' => 10,
     * ]
     *
     * $attributeMap:
     * [
     *   'name' => ProductAttribute(type=text),
     *   'price' => ProductAttribute(type=decimal),
     *   'qty' => ProductAttribute(type=int),
     * ]
     *
     * @param array<string, mixed> $values
     * @param array<string, ProductAttribute> $attributeMap
     */
    protected function attachAttributeValues(Product $product, array $values, array $attributeMap): void
    {
        foreach ($values as $code => $value) {
            self::assertArrayHasKey($code, $attributeMap, sprintf(
                'Attribute "%s" is missing in provided attribute map.',
                $code,
            ));

            $attribute = $attributeMap[$code];
            $type = $attribute->getType();

            match ($type) {
                'text' => $this->attachTextValue($product, $attribute, (string) $value),
                'int' => $this->attachIntValue($product, $attribute, (int) $value),
                'decimal' => $this->attachDecimalValue($product, $attribute, (float) $value),
                'image' => $this->attachImageValue($product, $attribute, (string) $value),
                default => self::fail(sprintf('Unsupported attribute type "%s" for code "%s".', $type, $code)),
            };
        }
    }
}
