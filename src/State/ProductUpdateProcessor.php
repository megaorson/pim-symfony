<?php
declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Dto\ProductOutput;
use App\ApiResource\Dto\ProductPatchInput;
use App\Entity\Product;
use App\Exception\Api\ProductNotFoundException;
use App\Repository\ProductRepository;
use App\Service\Eav\AttributeValueUpdater;
use App\Service\Product\Factory\ProductOutputContextFactory;
use App\Service\Product\Factory\ProductOutputFactory;
use App\Service\Product\Validation\ProductRequiredAttributesValidator;
use App\Service\ProductAttributeLocator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductUpdateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProductRepository $productRepository,
        private readonly ProductAttributeLocator $productAttributeLocator,
        private readonly AttributeValueUpdater $attributeValueUpdater,
        private readonly ProductOutputFactory $productOutputFactory,
        private readonly ProductOutputContextFactory $productOutputContextFactory,
        private readonly TranslatorInterface $translator,
        private readonly ProductRequiredAttributesValidator $productRequiredAttributesValidator,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ProductOutput
    {
        \assert($data instanceof ProductPatchInput);

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

        if ($data->sku !== null) {
            $product->setSku(trim($data->sku));
        }

        foreach ($data->attributes as $code => $value) {
            $attribute = $this->productAttributeLocator->getByCode((string) $code);
            $this->attributeValueUpdater->upsert($product, $attribute, $value);
        }

        $this->productRequiredAttributesValidator->validate($product);

        $this->em->flush();

        return $this->productOutputFactory->create(
            $product,
            $this->productOutputContextFactory->createAllFieldsContext()
        );
    }
}
