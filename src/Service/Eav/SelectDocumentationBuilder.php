<?php
declare(strict_types=1);

namespace App\Service\Eav;

use App\Service\Product\Field\ProductCollectionFieldProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class SelectDocumentationBuilder
{
    public function __construct(
        private ProductCollectionFieldProvider $fieldProvider,
        private TranslatorInterface $translator,
    ) {
    }

    public function buildSelectDescription(): string
    {
        return $this->translator->trans(
            'eav.select.description',
            [
                '%fields%' => implode(', ', $this->fieldProvider->getSelectableFields()),
            ]
        );
    }

    public function buildSelectExample(): string
    {
        $fields = $this->fieldProvider->getSelectableFields();

        $preferred = ['id', 'sku', 'price'];
        $selected = [];

        foreach ($preferred as $field) {
            if (in_array($field, $fields, true)) {
                $selected[] = $field;
            }
        }

        if ($selected !== []) {
            return implode(',', $selected);
        }

        return implode(',', array_slice($fields, 0, min(3, count($fields))));
    }
}
