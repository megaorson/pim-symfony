<?php
declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Product;
use App\DTO\ProductOutput;

class ProductOutputProcessor implements ProcessorInterface
{
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): mixed {
        if ($data instanceof Product) {
            return $this->transform($data);
        }

        if (is_iterable($data)) {
            $result = [];

            foreach ($data as $product) {
                $result[] = $this->transform($product);
            }

            return $result;
        }

        return $data;
    }

    private function transform(Product $product): ProductOutput
    {
        $dto = new ProductOutput();
        $dto->id = $product->getId();
        $dto->sku = $product->getSku();

        foreach ($product->getAllAttributeValues() as $value) {
            $dto->attributes[$value->getAttribute()->getCode()] = $value->getValue();
        }

        return $dto;
    }
}
