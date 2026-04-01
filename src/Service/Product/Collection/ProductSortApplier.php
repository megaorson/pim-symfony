<?php
declare(strict_types=1);

namespace App\Service\Product\Collection;

use App\Service\Eav\AttributeMetadataProvider;
use App\Service\Eav\AttributeTypeRegistry;
use App\Service\Eav\Dto\AttributeMetadata;
use App\Service\Product\Field\ProductSystemFieldRegistry;
use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductSortApplier implements CollectionApplierInterface
{
    public function __construct(
        private readonly ProductSystemFieldRegistry $systemFieldRegistry,
        private readonly AttributeMetadataProvider $attributeMetadataProvider,
        private readonly AttributeTypeRegistry $attributeTypeRegistry,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function apply(QueryBuilder $qb, ProductCollectionContext $context, string $rootAlias = 'p'): void
    {
        if ($context->sorts === []) {
            $qb->addOrderBy(
                sprintf('%s.%s', $rootAlias, $this->systemFieldRegistry->getDoctrineField('id')),
                'DESC'
            );

            return;
        }

        $requestedAttributeCodes = $this->collectRequestedAttributeCodes($context);
        $metadataMap = $this->attributeMetadataProvider->getByCodes($requestedAttributeCodes);

        $joinIndex = 0;
        $sortedByIdExplicitly = false;

        foreach ($context->sorts as $sort) {
            $field = $sort['field'];
            $direction = $sort['direction'];

            if ($field === 'id') {
                $sortedByIdExplicitly = true;
            }

            if ($this->systemFieldRegistry->isSystemField($field)) {
                if (!$this->systemFieldRegistry->isSortable($field)) {
                    throw new \InvalidArgumentException(
                        $this->translator->trans('eav.sort.field_not_sortable', ['%field%' => $field])
                    );
                }

                $qb->addOrderBy(
                    sprintf('%s.%s', $rootAlias, $this->systemFieldRegistry->getDoctrineField($field)),
                    $direction
                );

                continue;
            }

            $metadata = $metadataMap[$field] ?? null;

            if (!$metadata instanceof AttributeMetadata) {
                throw new \InvalidArgumentException(
                    $this->translator->trans('eav.sort.unknown_field', ['%field%' => $field])
                );
            }

            if (!$metadata->sortable) {
                throw new \InvalidArgumentException(
                    $this->translator->trans('eav.sort.field_not_sortable', ['%field%' => $field])
                );
            }

            $this->applyAttributeSort(
                $qb,
                $rootAlias,
                $metadata,
                $direction,
                $joinIndex++
            );
        }

        if (!$sortedByIdExplicitly) {
            $qb->addOrderBy(
                sprintf('%s.%s', $rootAlias, $this->systemFieldRegistry->getDoctrineField('id')),
                'DESC'
            );
        }
    }

    /**
     * @return list<string>
     */
    private function collectRequestedAttributeCodes(ProductCollectionContext $context): array
    {
        $codes = [];

        foreach ($context->sorts as $sort) {
            $field = $sort['field'];

            if ($this->systemFieldRegistry->isSystemField($field)) {
                continue;
            }

            $codes[] = $field;
        }

        return array_values(array_unique($codes));
    }

    private function applyAttributeSort(
        QueryBuilder $qb,
        string $rootAlias,
        AttributeMetadata $metadata,
        string $direction,
        int $index
    ): void {
        $valueEntityClass = $this->attributeTypeRegistry->getValueEntityClass($metadata->type);
        $joinAlias = 'sort_' . $index;
        $attributeParameter = 'sort_attribute_' . $index;
        $sortSelectAlias = 'sort_value_' . $index;

        $qb
            ->leftJoin(
                $valueEntityClass,
                $joinAlias,
                'WITH',
                sprintf(
                    '%s.product = %s AND %s.attribute = :%s',
                    $joinAlias,
                    $rootAlias,
                    $joinAlias,
                    $attributeParameter
                )
            )
            ->setParameter($attributeParameter, $metadata->id)
            ->addSelect(sprintf('%s.value AS HIDDEN %s', $joinAlias, $sortSelectAlias))
            ->addOrderBy($sortSelectAlias, $direction);
    }
}
