<?php
declare(strict_types=1);

namespace App\Service\Eav;

use App\Service\Product\Field\ProductCollectionFieldProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class SortDocumentationBuilder
{
    public function __construct(
        private ProductCollectionFieldProvider $fieldProvider,
        private TranslatorInterface $translator,
    ) {
    }

    public function buildSortDescription(): string
    {
        return $this->translator->trans(
            'eav.sort.description',
            [
                '%fields%' => implode(', ', $this->fieldProvider->getSortableFields()),
            ]
        );
    }

    public function buildSortExample(): string
    {
        $fields = $this->fieldProvider->getSortableFields();

        if (in_array('sku', $fields, true) && in_array('price', $fields, true)) {
            return 'sku,-price';
        }

        if (in_array('sku', $fields, true)) {
            return 'sku';
        }

        if ($fields !== []) {
            return $fields[0];
        }

        return 'sku';
    }
}
