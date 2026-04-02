<?php
declare(strict_types=1);

namespace App\Service\Eav\AttributeValueHandler;

use App\Entity\Product;
use App\Entity\ProductAttribute;
use App\Entity\ProductAttributeTypeInterface;
use App\Exception\Api\InvalidAttributeValueException;
use App\Service\Eav\AttributeTypeRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractAttributeValueHandler implements AttributeValueHandlerInterface
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
        protected readonly AttributeTypeRegistry $attributeTypeRegistry,
    ) {
    }

    public function supports(string $attributeType): bool
    {
        return $attributeType === $this->getAttributeType();
    }

    public function create(Product $product, ProductAttribute $attribute, mixed $rawValue): ProductAttributeTypeInterface
    {
        $normalizedValue = $this->normalize($attribute, $rawValue);

        /** @var ProductAttributeTypeInterface $valueEntity */
        $valueEntity = $this->attributeTypeRegistry->create($attribute->getType());
        $valueEntity->setAttribute($attribute);
        $valueEntity->setValue($normalizedValue);

        $this->attachValueToProduct($product, $valueEntity);

        return $valueEntity;
    }

    public function update(ProductAttributeTypeInterface $valueEntity, ProductAttribute $attribute, mixed $rawValue): void
    {
        $normalizedValue = $this->normalize($attribute, $rawValue);

        $valueEntity->setAttribute($attribute);
        $valueEntity->setValue($normalizedValue);
    }

    protected function createInvalidValueException(
        string $code,
        string $translationKey,
        array $parameters = [],
    ): InvalidAttributeValueException {
        return new InvalidAttributeValueException(
            $this->translator->trans(
                $translationKey,
                array_merge(['%code%' => $code], $parameters)
            ),
            $code
        );
    }

    protected function attachValueToProduct(Product $product, ProductAttributeTypeInterface $valueEntity): void
    {
        $method = $this->resolveProductAttachMethod($valueEntity);

        if ($method !== null && method_exists($product, $method)) {
            $product->{$method}($valueEntity);
            return;
        }

        $valueEntity->setProduct($product);
    }

    protected function resolveProductAttachMethod(ProductAttributeTypeInterface $valueEntity): ?string
    {
        $className = $valueEntity::class;
        $shortName = substr($className, (int) strrpos($className, '\\') + 1);

        if (!str_starts_with($shortName, 'ProductAttributeValue')) {
            return null;
        }

        $suffix = substr($shortName, strlen('ProductAttributeValue'));

        if ($suffix === '' || $suffix === false) {
            return null;
        }

        return 'add' . $suffix . 'Value';
    }

    abstract public function getAttributeType(): string;

    abstract protected function normalize(ProductAttribute $attribute, mixed $rawValue): mixed;
}
