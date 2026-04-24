<?php
declare(strict_types=1);

namespace App\Service\Product\Flat\Read;

use App\Exception\Api\InvalidSelectException;
use App\Exception\Api\InvalidSortException;
use App\Service\Product\Collection\ProductCollectionContext;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ProductFlatQueryPlanner
{
    public function __construct(
        private ProductFlatFieldMap $fieldMap,
        private TranslatorInterface $translator,
    ) {
    }

    public function build(ProductCollectionContext $context): ProductFlatQueryPlan
    {
        $selectedFields = $this->resolveSelect($context);
        $sorts = $this->resolveSorts($context);

        return new ProductFlatQueryPlan(
            selectedFields: $selectedFields,
            sorts: $sorts,
            rawFilter: $context->filter,
            limit: $context->limit,
            offset: $context->offset,
            page: $context->page,
        );
    }

    /**
     * @return list<string>
     */
    private function resolveSelect(ProductCollectionContext $context): array
    {
        $selectableMap = $this->fieldMap->getSelectableFieldToColumnMap();

        if ($context->shouldReturnAllFields()) {
            return array_keys($selectableMap);
        }

        $result = [];

        foreach ($context->selectedFields as $field) {
            if (!isset($selectableMap[$field])) {
                throw new InvalidSelectException(
                    $this->translator->trans('eav.select.unknown_field', ['%field%' => $field])
                );
            }

            $result[] = $field;
        }

        return array_values(array_unique($result));
    }

    /**
     * @return list<array{field: string, direction: 'ASC'|'DESC'}>
     */
    private function resolveSorts(ProductCollectionContext $context): array
    {
        $sortableMap = $this->fieldMap->getSortableFieldToColumnMap();

        if ($context->sorts === []) {
            return [
                ['field' => 'id', 'direction' => 'DESC'],
            ];
        }

        $result = [];
        $sortedByIdExplicitly = false;

        foreach ($context->sorts as $sort) {
            $field = $sort['field'];
            $direction = $sort['direction'];

            if (!isset($sortableMap[$field])) {
                throw new InvalidSortException(
                    $this->translator->trans('eav.sort.unknown_field', ['%field%' => $field])
                );
            }

            if ($field === 'id') {
                $sortedByIdExplicitly = true;
            }

            $result[] = [
                'field' => $field,
                'direction' => $direction,
            ];
        }

        if (!$sortedByIdExplicitly) {
            $result[] = ['field' => 'id', 'direction' => 'DESC'];
        }

        return $result;
    }
}
