<?php
declare(strict_types=1);

namespace App\Service\Product\Collection\Read\Sort;

use App\Exception\Api\InvalidSortException;
use App\Service\Eav\AttributeTypeRegistry;
use App\Service\Eav\Dto\AttributeMetadata;
use App\Service\Product\Collection\Read\Filter\OptimizedFilterSource;
use App\Service\Product\Collection\Read\ProductCollectionQueryPlan;
use App\Service\Product\Field\ProductSystemFieldRegistry;
use App\Service\ProductAttributeValue\ClassNameToTableName;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class SortSqlBuilder
{
    public function __construct(
        private ProductSystemFieldRegistry $systemFieldRegistry,
        private AttributeTypeRegistry $attributeTypeRegistry,
        private ClassNameToTableName $classNameToTableName,
        private TranslatorInterface $translator,
    ) {
    }

    public function apply(
        QueryBuilder $qb,
        ProductCollectionQueryPlan $plan,
        string $rootAlias = 'p',
        ?string $filterAlias = null,
        ?OptimizedFilterSource $optimizedSource = null,
    ): void {
        $joinIndex = 0;

        foreach ($plan->sorts as $sort) {
            $field = $sort['field'];
            $direction = $sort['direction'];

            if (
                $filterAlias !== null
                && $optimizedSource !== null
                && $optimizedSource->hasProjectedField($field)
            ) {
                $qb->addOrderBy(
                    sprintf('%s.%s', $filterAlias, $optimizedSource->getProjectedColumn($field)),
                    $direction
                );
                continue;
            }

            if ($this->systemFieldRegistry->isSystemField($field)) {
                $qb->addOrderBy(
                    sprintf('%s.%s', $rootAlias, $this->systemFieldRegistry->getDoctrineField($field)),
                    $direction
                );
                continue;
            }

            $metadata = $plan->sortAttributesByCode[$field] ?? null;

            if (!$metadata instanceof AttributeMetadata) {
                throw new InvalidSortException(
                    $this->translator->trans('eav.sort.unknown_field', ['%field%' => $field])
                );
            }

            $this->applyAttributeSort($qb, $metadata, $direction, $rootAlias, $joinIndex++);
        }
    }

    private function applyAttributeSort(
        QueryBuilder $qb,
        AttributeMetadata $metadata,
        string $direction,
        string $rootAlias,
        int $index,
    ): void {
        $tableName = $this->resolveTableNameByType($metadata->type);
        $joinAlias = 'sort_' . $index;
        $attributeParam = 'sort_attr_' . $index;

        $qb->leftJoin(
            $rootAlias,
            $tableName,
            $joinAlias,
            sprintf(
                '%s.product_id = %s.id AND %s.attribute_id = :%s',
                $joinAlias,
                $rootAlias,
                $joinAlias,
                $attributeParam
            )
        );

        $qb->setParameter($attributeParam, $metadata->id);

        $qb->addOrderBy(sprintf('%s.value', $joinAlias), $direction);
    }

    private function resolveTableNameByType(string $type): string
    {
        return $this->classNameToTableName->execute(
            $this->attributeTypeRegistry->getValueEntityClass($type)
        );
    }
}
