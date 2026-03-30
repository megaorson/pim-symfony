<?php
declare(strict_types=1);

namespace App\Service\Product\Collection;

use App\Service\Eav\AttributeMetadataProvider;
use App\Service\Eav\AttributeTypeRegistry;
use App\Service\Eav\Dto\AttributeMetadata;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AutoconfigureTag('app.product.collection_applier')]
#[AsTaggedItem(priority: -200)]
final readonly class ProductSortApplier implements CollectionApplierInterface
{
    /** @var array<string, string> */
    private const BASE_SORT_FIELDS = [
        'id' => 'id',
        'sku' => 'sku',
    ];

    public function __construct(
        private AttributeMetadataProvider $attributeMetadataProvider,
        private AttributeTypeRegistry $attributeTypeRegistry,
        private TranslatorInterface $translator,
    ) {
    }

    public function apply(QueryBuilder $qb, ProductCollectionContext $context, string $rootAlias = 'p'): void
    {
        if ($context->sorts === []) {
            $qb->addOrderBy($rootAlias . '.id', 'DESC');
            return;
        }

        $requestedAttributeCodes = [];
        foreach ($context->sorts as $sort) {
            if (!isset(self::BASE_SORT_FIELDS[$sort['field']])) {
                $requestedAttributeCodes[] = $sort['field'];
            }
        }

        $metadataMap = $this->attributeMetadataProvider->getByCodes($requestedAttributeCodes);
        $index = 0;

        foreach ($context->sorts as $sort) {
            $field = $sort['field'];
            $direction = $sort['direction'];

            if (isset(self::BASE_SORT_FIELDS[$field])) {
                $qb->addOrderBy($rootAlias . '.' . self::BASE_SORT_FIELDS[$field], $direction);
                continue;
            }

            $metadata = $metadataMap[$field] ?? null;
            if (!$metadata instanceof AttributeMetadata) {
                throw new \InvalidArgumentException($this->translator->trans('eav.sort.unknown_field', ['%field%' => $field]));
            }

            $this->applyAttributeSort($qb, $rootAlias, $metadata, $direction, $index++);
        }
    }

    private function applyAttributeSort(QueryBuilder $qb, string $rootAlias, AttributeMetadata $metadata, string $direction, int $index): void
    {
        $valueEntityClass = $this->attributeTypeRegistry->getValueEntityClass($metadata->type);
        $joinAlias = 'sort_' . $index;
        $attributeParameter = 'sort_attribute_' . $index;

        $qb
            ->leftJoin(
                $valueEntityClass,
                $joinAlias,
                'WITH',
                sprintf('%s.product = %s AND %s.attribute = :%s', $joinAlias, $rootAlias, $joinAlias, $attributeParameter),
            )
            ->setParameter($attributeParameter, $metadata->id)
            ->addOrderBy($joinAlias . '.value', $direction);
    }
}
