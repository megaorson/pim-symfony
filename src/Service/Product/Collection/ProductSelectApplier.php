<?php
declare(strict_types=1);

namespace App\Service\Product\Collection;

use App\Service\Eav\AttributeMetadataProvider;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AutoconfigureTag('app.product.collection_applier')]
#[AsTaggedItem(priority: 200)]
final readonly class ProductSelectApplier implements CollectionApplierInterface
{
    private const STATIC_FIELDS = [
        'id',
        'sku',
        'createdAt',
        'updatedAt',
    ];

    public function __construct(
        private AttributeMetadataProvider $attributeMetadataProvider,
        private TranslatorInterface $translator,
    ) {
    }

    public function apply(QueryBuilder $qb, ProductCollectionContext $context, string $rootAlias = 'p'): void
    {
        if ($context->selectedFields === [] || $context->shouldReturnAllFields()) {
            return;
        }

        $requestedAttributeCodes = array_values(array_filter(
            $context->selectedFields,
            static fn (string $field): bool => !in_array($field, self::STATIC_FIELDS, true),
        ));

        if ($requestedAttributeCodes === []) {
            return;
        }

        $metadataMap = $this->attributeMetadataProvider->getByCodes($requestedAttributeCodes);

        foreach ($requestedAttributeCodes as $code) {
            if (!isset($metadataMap[$code])) {
                throw new \InvalidArgumentException(
                    $this->translator->trans('eav.select.unknown_field', ['%field%' => $code])
                );
            }
        }
    }
}
