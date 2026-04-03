<?php
declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Dto\ProductAttributeOutput;
use App\Repository\ProductAttributeRepository;
use App\Service\ProductAttribute\Factory\ProductAttributeOutputFactory;

final class ProductAttributeCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly ProductAttributeRepository $productAttributeRepository,
        private readonly ProductAttributeOutputFactory $productAttributeOutputFactory,
    ) {
    }

    /**
     * @return ProductAttributeOutput[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $attributes = $this->productAttributeRepository->findBy([], ['id' => 'ASC']);

        $result = [];

        foreach ($attributes as $attribute) {
            $result[] = $this->productAttributeOutputFactory->create($attribute);
        }

        return $result;
    }
}
