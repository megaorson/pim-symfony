<?php
declare(strict_types=1);

namespace App\Service\ProductAttribute;

use App\Entity\ProductAttribute;
use Doctrine\ORM\EntityManagerInterface;

final class ProductAttributeDeleteService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function delete(ProductAttribute $attribute): void
    {
        $this->em->remove($attribute);
    }
}
