<?php
declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Dto\ProductAttributeCollectionOutput;
use App\Repository\ProductAttributeRepository;
use App\Service\ProductAttribute\Factory\ProductAttributeOutputFactory;
use Symfony\Component\HttpFoundation\RequestStack;

final class ProductAttributeCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly ProductAttributeRepository $productAttributeRepository,
        private readonly ProductAttributeOutputFactory $productAttributeOutputFactory,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ProductAttributeCollectionOutput
    {
        $request = $this->requestStack->getCurrentRequest();

        $page = max(1, (int) $request?->query->get('page', 1));
        $limit = max(1, (int) $request?->query->get('limit', 10));
        $offset = ($page - 1) * $limit;

        $totalItems = $this->productAttributeRepository->count([]);
        $attributes = $this->productAttributeRepository->findBy([], ['id' => 'ASC'], $limit, $offset);

        $items = [];

        foreach ($attributes as $attribute) {
            $items[] = $this->productAttributeOutputFactory->create($attribute);
        }

        return new ProductAttributeCollectionOutput(
            items: $items,
            totalItems: $totalItems,
            page: $page,
            limit: $limit,
            offset: $offset,
        );
    }
}
