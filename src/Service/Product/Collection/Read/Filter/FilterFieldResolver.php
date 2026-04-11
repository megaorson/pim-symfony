<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Filter;

use App\Exception\Api\InvalidFilterException;
use App\Service\Eav\AttributeMetadataProvider;
use App\Service\Eav\Dto\AttributeMetadata;
use App\Service\Product\Field\ProductSystemFieldRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class FilterFieldResolver
{
    public function __construct(
        private ProductSystemFieldRegistry $systemFieldRegistry,
        private AttributeMetadataProvider $attributeMetadataProvider,
        private TranslatorInterface $translator,
    ) {
    }

    public function resolve(string $field): FilterFieldDefinition
    {
        if ($this->systemFieldRegistry->isSystemField($field)) {
            if (!$this->systemFieldRegistry->isFilterable($field)) {
                throw new InvalidFilterException(
                    $this->translator->trans('eav.filter.field_not_filterable', ['%field%' => $field])
                );
            }

            return new FilterFieldDefinition(
                field: $field,
                isSystemField: true,
                systemColumn: $this->systemFieldRegistry->getDoctrineField($field),
            );
        }

        $metadataMap = $this->attributeMetadataProvider->getByCodes([$field]);
        $metadata = $metadataMap[$field] ?? null;

        if (!$metadata instanceof AttributeMetadata) {
            throw new InvalidFilterException(
                $this->translator->trans('eav.filter.unknown_field', ['%field%' => $field])
            );
        }

        if (!$metadata->filterable) {
            throw new InvalidFilterException(
                $this->translator->trans('eav.filter.field_not_filterable', ['%field%' => $field])
            );
        }

        return new FilterFieldDefinition(
            field: $field,
            isSystemField: false,
            attributeMetadata: $metadata,
        );
    }
}
