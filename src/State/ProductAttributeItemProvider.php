<?php
declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Dto\ProductAttributeOutput;
use App\Entity\ProductAttribute;
use App\Exception\Api\ProductAttributeNotFoundException;
use App\Repository\ProductAttributeRepository;
use App\Service\ProductAttribute\Factory\ProductAttributeOutputFactory;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductAttributeItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly ProductAttributeRepository $productAttributeRepository,
        private readonly ProductAttributeOutputFactory $productAttributeOutputFactory,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ProductAttributeOutput
    {
        $attribute = $this->productAttributeRepository->find($uriVariables['id'] ?? null);

        if (!$attribute instanceof ProductAttribute) {
            throw new ProductAttributeNotFoundException(
                $this->translator->trans(
                    'product_attribute.not_found',
                    ['%id%' => (string) ($uriVariables['id'] ?? '')]
                ),
                $uriVariables['id'] ?? null
            );
        }

        return $this->productAttributeOutputFactory->create($attribute);
    }
}
