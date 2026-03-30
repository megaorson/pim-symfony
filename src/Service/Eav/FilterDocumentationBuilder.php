<?php
declare(strict_types=1);

namespace App\Service\Eav;

use Symfony\Contracts\Translation\TranslatorInterface;

final class FilterDocumentationBuilder
{
    public function __construct(
        private readonly FilterFieldProvider $fieldProvider,
        private readonly TranslatorInterface $translator
    ) {
    }

    public function buildFilterDescription(): string
    {
        $fields = $this->fieldProvider->getFilterableCodes();

        return $this->translator->trans('eav.filter.description', [
            '%fields%' => implode(', ', $fields),
        ]);
    }

    public function buildFilterExample(): string
    {
        return $this->translator->trans('eav.filter.example');
    }

    public function buildSelectDescription(): string
    {
        $fields = $this->fieldProvider->getSelectableCodes();

        return $this->translator->trans('eav.select.description', [
            '%fields%' => implode(', ', $fields),
        ]);
    }

    public function buildSelectExample(): string
    {
        return $this->translator->trans('eav.select.example');
    }
}
