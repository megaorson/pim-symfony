<?php
declare(strict_types=1);

namespace App\Entity;

abstract class ProductAttributeValueAbstract implements ProductAttributeTypeInterface
{
    public const TYPE = 'default';

    public function getType()
    : string
    {
        return static::TYPE;
    }
}
