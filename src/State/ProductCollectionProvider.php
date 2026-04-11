<?php
declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Service\Product\Collection\ProductCollectionContextFactory;
use App\Service\Product\Collection\Read\ProductCollectionReadService;

final readonly class ProductCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ProductCollectionContextFactory $contextFactory,
        private ProductCollectionReadService $readService,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $filters = is_array($context['filters'] ?? null) ? $context['filters'] : [];
        $collectionContext = $this->contextFactory->createFromFilters($filters);

        return $this->readService->getCollection($collectionContext);
    }
}
