<?php
declare(strict_types=1);

namespace App\State;

use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Product;
use App\DTO\ProductOutput;
use Doctrine\ORM\EntityManagerInterface;
use App\DTO\ProductCollectionOutput;

class ProductProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): object|array|null {
        $repo = $this->em->getRepository(Product::class);

        if (isset($uriVariables['id'])) {
            $product = $repo->find($uriVariables['id']);

            return $product ? $this->transform($product) : null;
        }

        $filters = $context['filters'] ?? [];

        $page = isset($filters['page']) ? (int)$filters['page'] : 1;
        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 10;

        $offset = ($page - 1) * $limit;

        $qb = $this->em->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $products = $qb->getQuery()->getResult();

        $totalItems = (int) $this->em->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $collectionDto = new ProductCollectionOutput();
        $collectionDto->page = $page;
        $collectionDto->limit = $limit;
        $collectionDto->totalItems = $totalItems;

        foreach ($products as $product) {
            $collectionDto->items[] = $this->transform($product);
        }

        return $collectionDto;
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
