<?php
declare(strict_types=1);

namespace App\Service\ProductAttribute\Factory;

use App\ApiResource\Dto\ProductAttributeOutput;
use App\Entity\ProductAttribute;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductAttributeOutputFactory
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public function create(ProductAttribute $attribute): ProductAttributeOutput
    {
        $id = $attribute->getId();

        if ($id === null) {
            throw new \LogicException(
                $this->translator->trans(
                    'product_attribute.output.id_is_null',
                    ['%code%' => $attribute->getCode()]
                )
            );
        }

        $output = new ProductAttributeOutput();
        $output->id = $id;
        $output->code = $attribute->getCode();
        $output->type = $attribute->getType();
        $output->name = $attribute->getName();
        $output->isRequired = $attribute->isRequired();
        $output->isFilterable = $attribute->isFilterable();
        $output->isSortable = $attribute->isSortable();
        $output->isSelectable = $attribute->isSelectable();
        $output->createdAt = $attribute->getCreatedAt();
        $output->updatedAt = $attribute->getUpdatedAt();

        return $output;
    }
}
