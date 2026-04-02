<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\ProductAttribute;
use App\Exception\Api\UnknownAttributeException;
use App\Repository\ProductAttributeRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductAttributeLocator
{
    public function __construct(
        private readonly ProductAttributeRepository $productAttributeRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getByCode(string $code): ProductAttribute
    {
        $attribute = $this->productAttributeRepository->findOneBy(['code' => $code]);

        if (!$attribute instanceof ProductAttribute) {
            throw new UnknownAttributeException(
                $this->translator->trans(
                    'product.attribute.not_found',
                    ['%code%' => $code]
                ),
                $code
            );
        }

        return $attribute;
    }
}
