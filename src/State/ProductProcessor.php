<?php
declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Dto\ProductInput;
use App\Entity\Product;
use App\Exception\Api\AbstractApiException;
use App\Exception\Api\UnknownAttributeException;
use App\Repository\ProductAttributeRepository;
use App\Repository\ProductRepository;
use App\Service\Eav\AttributeValueWriter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductProcessor extends AbstractProduct implements ProcessorInterface
{
    public function __construct(
        EntityManagerInterface $em,
        ProductRepository $productRepository,
        private readonly ProductAttributeRepository $productAttributeRepository,
        private readonly AttributeValueWriter $attributeValueWriter,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct($em, $productRepository);
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Product|null
    {
        if ($operation instanceof DeleteOperationInterface) {
            return $this->processDelete($uriVariables);
        }

        if ($operation instanceof Post) {
            /** @var ProductInput $data */
            return $this->processCreate($data);
        }

        if ($operation instanceof Patch) {
            /** @var ProductInput $data */
            return $this->processPatch($data, $uriVariables);
        }

        throw new \LogicException(sprintf('Unsupported operation "%s".', $operation::class));
    }

    private function processCreate(ProductInput $data): Product
    {
        $product = new Product();
        $product->setSku(trim($data->sku));

        $this->em->persist($product);

        foreach ($data->attributes as $code => $value) {
            $attribute = $this->productAttributeRepository->findOneBy(['code' => $code]);

            if (!$attribute) {
                throw new UnknownAttributeException(
                    $this->translator->trans('product.attribute.not_found', ['%code%' => (string) $code]),
                    (string) $code
                );
            }

            $this->attributeValueWriter->write($product, $attribute, $value);
        }

        $this->em->flush();

        return $product;
    }

    private function processPatch(ProductInput $data, array $uriVariables): Product
    {
        $product = $this->productRepository->find($uriVariables['id'] ?? null);

        if (!$product instanceof Product) {
            throw new \RuntimeException('Product not found.');
        }

        if ($data->sku !== '') {
            $product->setSku(trim($data->sku));
        }

        foreach ($data->attributes as $code => $value) {
            $attribute = $this->productAttributeRepository->findOneBy(['code' => $code]);

            if (!$attribute) {
                throw new UnknownAttributeException(
                    $this->translator->trans('product.attribute.not_found', ['%code%' => (string) $code]),
                    (string) $code
                );
            }

            $this->attributeValueWriter->write($product, $attribute, $value);
        }

        $this->em->flush();

        return $product;
    }

    private function processDelete(array $uriVariables): ?Product
    {
        $product = $this->productRepository->find($uriVariables['id'] ?? null);

        if ($product instanceof Product) {
            $this->em->remove($product);
            $this->em->flush();
        }

        return null;
    }
}
