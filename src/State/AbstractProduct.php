<?php
declare(strict_types=1);

namespace App\State;

use App\ApiResource\Dto\ProductOutput;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractProduct
{
    public function __construct(
        protected readonly EntityManagerInterface $em,
        protected readonly ProductRepository $productRepository
    ) {
    }

    protected function transform(Product $product): ProductOutput
    {
        $dto = new ProductOutput($product->getId(), $product->getSku());
        foreach ($product->getAllAttributeValues() as $value) {
            $dto->attributes[$value->getAttribute()->getCode()] = $value->getValue();
        }
        return $dto;
    }
}
