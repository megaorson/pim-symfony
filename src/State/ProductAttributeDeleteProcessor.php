<?php
declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\ProductAttribute;
use App\Exception\Api\ProductAttributeInUseException;
use App\Exception\Api\ProductAttributeNotFoundException;
use App\Repository\ProductAttributeRepository;
use App\Service\Eav\ProductAttributeUsageChecker;
use App\Service\ProductAttribute\ProductAttributeDeleteService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductAttributeDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProductAttributeRepository $productAttributeRepository,
        private readonly ProductAttributeDeleteService $productAttributeDeleteService,
        private readonly TranslatorInterface $translator,
        private readonly ProductAttributeUsageChecker $usageChecker,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
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

        if ($this->usageChecker->isUsed($attribute)) {
            throw new ProductAttributeInUseException(
                $this->translator->trans(
                    'product_attribute.in_use',
                    [
                        '%id%' => (string) $attribute->getId(),
                        '%code%' => $attribute->getCode(),
                    ]
                )
            );
        }

        $this->productAttributeDeleteService->delete($attribute);
        $this->em->flush();
    }
}
