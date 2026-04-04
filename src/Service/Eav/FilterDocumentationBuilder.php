<?php
declare(strict_types=1);

namespace App\Service\Eav;

use App\Service\Product\Field\ProductCollectionFieldProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class FilterDocumentationBuilder
{
    public function __construct(
        private ProductCollectionFieldProvider $fieldProvider,
        private TranslatorInterface $translator,
    ) {
    }

    public function buildFilterDescription(): string
    {
        return $this->translator->trans(
            'eav.filter.description',
            [
                '%fields%' => implode(', ', $this->fieldProvider->getFilterableFields()),
            ]
        );
    }

    public function buildFilterExample(): string
    {
        $fields = $this->fieldProvider->getFilterableFields();

        if (in_array('sku', $fields, true)) {
            return 'sku BEGINS "test"';
        }

        if (in_array('price', $fields, true)) {
            return 'price GT 1000';
        }

        if ($fields !== []) {
            return sprintf('%s EQ "value"', $fields[0]);
        }

        return 'sku BEGINS "test"';
    }
}
