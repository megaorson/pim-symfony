<?php
declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Dto\ProductOutput;
use App\ApiResource\Dto\ProductPatchInput;
use App\Entity\Product;
use App\Exception\Api\ProductNotFoundException;
use App\Repository\ProductRepository;
use App\Service\Product\Factory\ProductOutputContextFactory;
use App\Service\Product\Factory\ProductOutputFactory;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductOutputFactory $productOutputFactory,
        private readonly ProductOutputContextFactory $productOutputContextFactory,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ProductOutput {

        /** @var ProductPatchInput $data */
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

        return $this->productOutputFactory->create(
            $product,
            $this->productOutputContextFactory->createAllFieldsContext()
        );
    }
}
