<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\ProductAttributeValueText;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductAttributeValueText>
 */
class ProductAttributeValueTextRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductAttributeValueText::class);
    }
}
