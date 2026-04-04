<?php
declare(strict_types=1);

namespace App\Service\Product\Validation;

use App\Entity\Product;
use App\Exception\Api\ProductMissingRequiredAttributesException;
use App\Service\Eav\AttributeMetadataProvider;
use App\Service\Product\Value\ProductAttributeValueResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ProductRequiredAttributesValidator
{
    public function __construct(
        private AttributeMetadataProvider $attributeMetadataProvider,
        private ProductAttributeValueResolver $valueResolver,
        private TranslatorInterface $translator,
    ) {
    }

    public function validate(Product $product): void
    {
        $missingCodes = [];

        foreach ($this->attributeMetadataProvider->getAllRequired() as $metadata) {
            $value = $this->valueResolver->getValueByCode($product, $metadata->code);

            if ($this->isEmpty($value)) {
                $missingCodes[] = $metadata->code;
            }
        }

        if ($missingCodes !== []) {
            throw new ProductMissingRequiredAttributesException(
                message: $this->translator->trans('product.error.missing_required_attributes'),
                codes: $missingCodes,
            );
        }
    }

    private function isEmpty(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value) && trim($value) === '') {
            return true;
        }

        if (is_array($value) && $value === []) {
            return true;
        }

        return false;
    }
}
