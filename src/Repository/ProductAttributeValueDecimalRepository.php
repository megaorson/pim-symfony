<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\ProductAttributeValueDecimal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductAttributeValueDecimal>
 */
class ProductAttributeValueDecimalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductAttributeValueDecimal::class);
    }
}
