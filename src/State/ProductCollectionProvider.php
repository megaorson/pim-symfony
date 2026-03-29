<?php
declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Dto\ProductCollectionOutput;
use App\ApiResource\Dto\ProductOutput;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\Eav\Filter\SmartEavFilterApplier;

final class ProductCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ProductRepository $productRepository,
        private SmartEavFilterApplier $smartEavFilterApplier,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ProductCollectionOutput
    {
        $qb = $this->productRepository->createQueryBuilder('p');

        $filters = $context['filters'] ?? [];

        $limit = isset($filters['limit']) ? max(1, (int) $filters['limit']) : 20;
        $offset = isset($filters['offset']) ? max(0, (int) $filters['offset']) : 0;
        $filterString = $filters['filter'] ?? null;

        if (is_string($filterString) && $filterString !== '') {
            $this->smartEavFilterApplier->apply($qb, $filterString, 'p');
        }

        $countQb = clone $qb;
        $total = (int) $countQb
            ->select('COUNT(DISTINCT p.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        /** @var Product[] $products */
        $products = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $items = array_map(
            fn (Product $product) => $this->mapProductToOutput($product),
            $products
        );

        return new ProductCollectionOutput(
            items: $items,
            total: $total,
            limit: $limit,
            offset: $offset,
        );
    }

    private function mapProductToOutput(Product $product): ProductOutput
    {
        $attributes = [];

        foreach ($product->getAllAttributeValues() as $value) {
            $attributeCode = $value->getAttribute()->getCode();

            $attributes[$attributeCode] = $value->getValue();
        }

        return new ProductOutput(
            id: $product->getId(),
            sku: $product->getSku(),
            attributes: $attributes,
        );
    }
}
