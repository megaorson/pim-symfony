<?php
declare(strict_types=1);

namespace App\State;

use ApiPlatform\State\ProcessorInterface;
use App\Dto\ProductInput;
use App\Entity\Product;
use App\Entity\ProductAttribute;
use App\Entity\ProductAttributeValueText;
use App\Entity\ProductAttributeValueDecimal;
use App\Entity\ProductAttributeValueInt;
use App\Entity\ProductAttributeValueImage;
use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\Metadata\Operation;

class ProductProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Product
    {
        /** @var ProductInput $data */

        $product = new Product();
        $product->setSku($data->sku);

        foreach ($data->attributes as $code => $value) {

            $attribute = $this->em->getRepository(ProductAttribute::class)
                ->findOneBy(['code' => $code]);

            if (!$attribute) {
                throw new \Exception("Attribute {$code} not found");
            }

            $valueEntity = $this->createValueEntity($attribute->getType(), $value);

            $valueEntity->setAttribute($attribute);
            $valueEntity->setProduct($product);

            $this->em->persist($valueEntity);
        }

        $this->em->persist($product);
        $this->em->flush();

        return $product;
    }

    private function createValueEntity(string $type, mixed $value)
    {
        return match ($type) {
            'text' => (new ProductAttributeValueText())->setValue((string)$value),
            'decimal' => (new ProductAttributeValueDecimal())->setValue((float)$value),
            'int' => (new ProductAttributeValueInt())->setValue((int)$value),
            'image' => (new ProductAttributeValueImage())->setValue((string)$value),
            default => throw new \Exception("Unknown type {$type}")
        };
    }
}
