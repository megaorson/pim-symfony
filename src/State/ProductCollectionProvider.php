<?php
declare(strict_types=1);

namespace App\State;

use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Metadata\Operation;
use App\DTO\ProductCollectionOutput;

class ProductCollectionProvider extends AbstractProduct implements ProviderInterface
{
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): object|array|null {
        $filters = $context['filters'] ?? [];

        $page = isset($filters['page']) ? (int)$filters['page'] : 1;
        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 10;

        $offset = ($page - 1) * $limit;

        $qb = $this->productRepository
            ->createQueryBuilder('p')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $products = $qb->getQuery()->getResult();

        $totalItems = (int) $this->productRepository
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
}
