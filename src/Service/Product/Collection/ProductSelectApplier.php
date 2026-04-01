<?php
declare(strict_types=1);

namespace App\Service\Product\Collection;

use App\Service\Eav\AttributeMetadataProvider;
use App\Service\Eav\Dto\AttributeMetadata;
use App\Service\Product\Field\ProductSystemFieldRegistry;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Exception\Api\InvalidSelectException;

#[AutoconfigureTag('app.product.collection_applier')]
#[AsTaggedItem(priority: 200)]
final readonly class ProductSelectApplier implements CollectionApplierInterface
{
    public function __construct(
        private ProductSystemFieldRegistry $systemFieldRegistry,
        private AttributeMetadataProvider $attributeMetadataProvider,
        private TranslatorInterface $translator,
    ) {
    }

    public function apply(QueryBuilder $qb, ProductCollectionContext $context, string $rootAlias = 'p'): void
    {
        if ($context->selectedFields === [] || $context->shouldReturnAllFields()) {
            return;
        }

        $requestedAttributeCodes = [];

        foreach ($context->selectedFields as $field) {
            if ($this->systemFieldRegistry->isSystemField($field)) {
                if (!$this->systemFieldRegistry->isSelectable($field)) {
                    throw new InvalidSelectException(
                        $this->translator->trans('eav.select.field_not_selectable', ['%field%' => $field])
                    );
                }

                continue;
            }

            $requestedAttributeCodes[] = $field;
        }

        if ($requestedAttributeCodes === []) {
            return;
        }

        $requestedAttributeCodes = array_values(array_unique($requestedAttributeCodes));
        $metadataMap = $this->attributeMetadataProvider->getByCodes($requestedAttributeCodes);

        foreach ($requestedAttributeCodes as $code) {
            $metadata = $metadataMap[$code] ?? null;

            if (!$metadata instanceof AttributeMetadata) {
                throw new InvalidSelectException(
                    $this->translator->trans('eav.select.unknown_field', ['%field%' => $code])
                );
            }

            if (!$metadata->selectable) {
                throw new InvalidSelectException(
                    $this->translator->trans('eav.select.field_not_selectable', ['%field%' => $code])
                );
            }
        }
    }
}
