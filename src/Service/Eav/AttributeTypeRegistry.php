<?php
declare(strict_types=1);

namespace App\Service\Eav;

use Symfony\Contracts\Translation\TranslatorInterface;

final class AttributeTypeRegistry
{
    /**
     * @param array<string, class-string> $valueEntityMap
     */
    public function __construct(
        private readonly array $valueEntityMap,
        private readonly TranslatorInterface $translator
    ) {
    }

    public function getValueEntityClass(string $type): string
    {
        if (!isset($this->valueEntityMap[$type])) {
            throw new \InvalidArgumentException($this->translator->trans(
                'eav.attribute.unsupported_type',
                ['%type%' => $type]
            ));
        }

        return $this->valueEntityMap[$type];
    }

    public function create(string $type): object
    {
        $entityClass = $this->getValueEntityClass($type);

        return new $entityClass();
    }

    /**
     * @return array<string, class-string>
     */
    public function all(): array
    {
        return $this->valueEntityMap;
    }
}
