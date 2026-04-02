<?php
declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Product;
use App\Exception\Api\ProductNotFoundException;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductDeleteProcessor extends AbstractProduct implements ProcessorInterface
{
    public function __construct(
        EntityManagerInterface $em,
        ProductRepository $productRepository,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct($em, $productRepository);
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $product = $this->productRepository->find($uriVariables['id'] ?? null);

        if (!$product instanceof Product) {
            throw new ProductNotFoundException(
                $this->translator->trans(
                    'product.not_found',
                    ['%id%' => (string) ($uriVariables['id'] ?? '')]
                ),
                $uriVariables['id'] ?? null
            );
        }

        $this->em->remove($product);
        $this->em->flush();

        return null;
    }
}
