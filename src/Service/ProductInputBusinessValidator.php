<?php
declare(strict_types=1);

namespace App\Service;

use App\ApiResource\Dto\ProductInput;
use App\Entity\ProductAttribute;
use App\Exception\Api\DuplicateSkuException;
use App\Exception\Api\UnknownAttributeException;
use App\Repository\ProductAttributeRepository;
use App\Repository\ProductRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductInputBusinessValidator
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ProductAttributeRepository $productAttributeRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @return array<string, ProductAttribute>
     */
    public function validateAndLoadAttributes(ProductInput $input): array
    {
        $sku = trim($input->sku);

        if ($this->productRepository->findOneBy(['sku' => $sku]) !== null) {
            throw new DuplicateSkuException(
                $this->translator->trans('product.create.duplicate_sku', ['%sku%' => $sku]),
                $sku
            );
        }

        $attributesByCode = [];

        foreach (array_keys($input->attributes) as $code) {
            $attributeCode = (string) $code;
            $attribute = $this->productAttributeRepository->findOneBy(['code' => $attributeCode]);

            if ($attribute === null) {
                throw new UnknownAttributeException(
                    $this->translator->trans('product.attribute.not_found', ['%code%' => $attributeCode]),
                    $attributeCode
                );
            }

            $attributesByCode[$attributeCode] = $attribute;
        }

        return $attributesByCode;
    }
}
