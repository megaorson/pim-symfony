<?php
declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Dto\ProductInput;
use App\ApiResource\Dto\ProductOutput;
use App\Entity\Product;
use App\Service\Eav\AttributeValueWriter;
use App\Service\Product\Factory\ProductOutputContextFactory;
use App\Service\Product\Factory\ProductOutputFactory;
use App\Service\Product\Validation\ProductRequiredAttributesValidator;
use App\Service\ProductAttributeLocator;
use Doctrine\ORM\EntityManagerInterface;

final class ProductCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProductAttributeLocator $productAttributeLocator,
        private readonly AttributeValueWriter $attributeValueWriter,
        private readonly ProductOutputFactory $productOutputFactory,
        private readonly ProductOutputContextFactory $productOutputContextFactory,
        private readonly ProductRequiredAttributesValidator $productRequiredAttributesValidator,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ProductOutput
    {
        \assert($data instanceof ProductInput);

        $product = new Product();
        $product->setSku(trim($data->sku));

        $this->em->persist($product);

        foreach ($data->attributes as $code => $value) {
            $attribute = $this->productAttributeLocator->getByCode((string) $code);
            $this->attributeValueWriter->write($product, $attribute, $value);
        }

        $this->productRequiredAttributesValidator->validate($product);

        $this->em->flush();

        return $this->productOutputFactory->create(
            $product,
            $this->productOutputContextFactory->createAllFieldsContext()
        );
    }
}
