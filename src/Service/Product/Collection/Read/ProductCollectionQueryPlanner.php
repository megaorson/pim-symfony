<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read;

use App\Exception\Api\InvalidSelectException;
use App\Exception\Api\InvalidSortException;
use App\Service\Eav\AttributeMetadataProvider;
use App\Service\Eav\Dto\AttributeMetadata;
use App\Service\Product\Collection\ProductCollectionContext;
use App\Service\Product\Field\ProductSystemFieldRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ProductCollectionQueryPlanner
{
    public function __construct(
        private ProductSystemFieldRegistry $systemFieldRegistry,
        private AttributeMetadataProvider $attributeMetadataProvider,
        private TranslatorInterface $translator,
    ) {
    }

    public function build(ProductCollectionContext $context): ProductCollectionQueryPlan
    {
        [$selectedSystemFields, $selectedAttributesByCode, $selectAllFields] = $this->resolveSelect($context);
        [$sorts, $sortAttributesByCode] = $this->resolveSorts($context);

        return new ProductCollectionQueryPlan(
            selectedSystemFields: $selectedSystemFields,
            selectedAttributesByCode: $selectedAttributesByCode,
            sorts: $sorts,
            sortAttributesByCode: $sortAttributesByCode,
            rawFilter: $context->filter,
            selectAllFields: $selectAllFields,
            hasExplicitSelect: $context->hasExplicitSelect(),
            limit: $context->limit,
            offset: $context->offset,
            page: $context->page,
        );
    }

    /**
     * @return array{0: list<string>, 1: array<string, AttributeMetadata>, 2: bool}
     */
    private function resolveSelect(ProductCollectionContext $context): array
    {
        if ($context->shouldReturnAllFields()) {
            return [
                ['sku'],
                [],
                true,
            ];
        }

        $selectedSystemFields = [];
        $requestedAttributeCodes = [];

        foreach ($context->selectedFields as $field) {
            if ($this->systemFieldRegistry->isSystemField($field)) {
                if (!$this->systemFieldRegistry->isSelectable($field)) {
                    throw new InvalidSelectException(
                        $this->translator->trans('eav.select.field_not_selectable', ['%field%' => $field])
                    );
                }

                $selectedSystemFields[] = $field;
                continue;
            }

            $requestedAttributeCodes[] = $field;
        }

        $requestedAttributeCodes = array_values(array_unique($requestedAttributeCodes));
        $metadataMap = $requestedAttributeCodes !== []
            ? $this->attributeMetadataProvider->getByCodes($requestedAttributeCodes)
            : [];

        $selectedAttributesByCode = [];

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

            $selectedAttributesByCode[$code] = $metadata;
        }

        return [
            array_values(array_unique($selectedSystemFields)),
            $selectedAttributesByCode,
            false,
        ];
    }

    /**
     * @return array{
     *   0: list<array{field: string, direction: 'ASC'|'DESC'}>,
     *   1: array<string, AttributeMetadata>
     * }
     */
    private function resolveSorts(ProductCollectionContext $context): array
    {
        if ($context->sorts === []) {
            return [
                [['field' => 'id', 'direction' => 'DESC']],
                [],
            ];
        }

        $requestedAttributeCodes = [];

        foreach ($context->sorts as $sort) {
            $field = $sort['field'];

            if ($this->systemFieldRegistry->isSystemField($field)) {
                continue;
            }

            $requestedAttributeCodes[] = $field;
        }

        $requestedAttributeCodes = array_values(array_unique($requestedAttributeCodes));
        $metadataMap = $requestedAttributeCodes !== []
            ? $this->attributeMetadataProvider->getByCodes($requestedAttributeCodes)
            : [];

        $result = [];
        $sortAttributesByCode = [];
        $sortedByIdExplicitly = false;

        foreach ($context->sorts as $sort) {
            $field = $sort['field'];
            $direction = $sort['direction'];

            if ($field === 'id') {
                $sortedByIdExplicitly = true;
            }

            if ($this->systemFieldRegistry->isSystemField($field)) {
                if (!$this->systemFieldRegistry->isSortable($field)) {
                    throw new InvalidSortException(
                        $this->translator->trans('eav.sort.field_not_sortable', ['%field%' => $field])
                    );
                }

                $result[] = [
                    'field' => $field,
                    'direction' => $direction,
                ];

                continue;
            }

            $metadata = $metadataMap[$field] ?? null;

            if (!$metadata instanceof AttributeMetadata) {
                throw new InvalidSortException(
                    $this->translator->trans('eav.sort.unknown_field', ['%field%' => $field])
                );
            }

            if (!$metadata->sortable) {
                throw new InvalidSortException(
                    $this->translator->trans('eav.sort.field_not_sortable', ['%field%' => $field])
                );
            }

            $sortAttributesByCode[$field] = $metadata;

            $result[] = [
                'field' => $field,
                'direction' => $direction,
            ];
        }

        if (!$sortedByIdExplicitly) {
            $result[] = ['field' => 'id', 'direction' => 'DESC'];
        }

        return [$result, $sortAttributesByCode];
    }
}
