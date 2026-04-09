<?php
declare(strict_types=1);

namespace App\Service\Eav;

use App\Entity\ProductAttribute;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ProductAttributeUsageChecker
{
    public function __construct(
        private EntityManagerInterface $em,
        private array $valueEntityMap,
    ) {
    }

    public function isUsed(ProductAttribute $attribute): bool
    {
        foreach ($this->valueEntityMap as $entityClass) {
            $count = (int) $this->em
                ->getRepository($entityClass)
                ->createQueryBuilder('v')
                ->select('COUNT(v.id)')
                ->andWhere('v.attribute = :attribute')
                ->setParameter('attribute', $attribute)
                ->getQuery()
                ->getSingleScalarResult();

            if ($count > 0) {
                return true;
            }
        }

        return false;
    }
}
