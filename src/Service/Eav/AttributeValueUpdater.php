<?php
declare(strict_types=1);

namespace App\Service\Eav;

use App\Entity\Product;
use App\Entity\ProductAttribute;
use App\Entity\ProductAttributeTypeInterface;
use Doctrine\ORM\EntityManagerInterface;

final class AttributeValueUpdater
{
    public function __construct(
        private readonly AttributeValueHandlerRegistry $attributeValueHandlerRegistry,
        private readonly AttributeValueWriter $attributeValueWriter,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function upsert(Product $product, ProductAttribute $attribute, mixed $rawValue): void
    {
        $existingValues = $this->findExistingValues($product, $attribute);

        if ($existingValues === []) {
            $this->attributeValueWriter->write($product, $attribute, $rawValue);
            return;
        }

        $handler = $this->attributeValueHandlerRegistry->get(
            $attribute->getType(),
            $attribute->getCode()
        );

        $primaryValue = array_shift($existingValues);
        $handler->update($primaryValue, $attribute, $rawValue);

        foreach ($existingValues as $duplicateValue) {
            $this->em->remove($duplicateValue);
        }
    }

    /**
     * @return list<ProductAttributeTypeInterface>
     */
    private function findExistingValues(Product $product, ProductAttribute $attribute): array
    {
        $result = [];

        foreach ($product->getAllAttributeValues() as $valueEntity) {
            $valueAttribute = $valueEntity->getAttribute();

            if ($valueAttribute?->getId() === $attribute->getId()) {
                $result[] = $valueEntity;
            }
        }

        return $result;
    }
}
