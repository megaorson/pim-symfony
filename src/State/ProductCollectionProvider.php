<?php
declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\Product\Collection\CollectionApplierInterface;
use App\Service\Product\Collection\ProductCollectionContextFactory;
use App\Service\Product\Collection\ProductCollectionResultMapper;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final readonly class ProductCollectionProvider implements ProviderInterface
{
    /**
     * @param iterable<CollectionApplierInterface> $collectionAppliers
     */
    public function __construct(
        private ProductRepository $productRepository,
        private ProductCollectionContextFactory $contextFactory,
        #[TaggedIterator('app.product.collection_applier')]
        private iterable $collectionAppliers,
        private ProductCollectionResultMapper $resultMapper,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $filters = is_array($context['filters'] ?? null) ? $context['filters'] : [];
        $collectionContext = $this->contextFactory->create($filters);

        $qb = $this->productRepository->createQueryBuilder('p');

        foreach ($this->collectionAppliers as $collectionApplier) {
            $collectionApplier->apply($qb, $collectionContext, 'p');
        }

        $countQb = clone $qb;
        $total = (int) $countQb
            ->select('COUNT(DISTINCT p.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        /** @var list<Product> $products */
        $products = $qb
            ->select('DISTINCT p')
            ->setFirstResult($collectionContext->offset)
            ->setMaxResults($collectionContext->limit)
            ->getQuery()
            ->getResult();

        return $this->resultMapper->mapCollection($products, $collectionContext, $total);
    }
}
