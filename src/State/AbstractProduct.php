<?php
declare(strict_types=1);

namespace App\State;

use App\DTO\ProductOutput;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ProductRepository;

abstract class AbstractProduct
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected ProductRepository $productRepository
    ) {}

    protected function transform(Product $product): ProductOutput
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
