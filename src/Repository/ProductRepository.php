<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return list<Product>
     */
    public function findBatchForFlatIndex(int $limit, int $offset, ?int $maxTotal = null): array
    {
        $effectiveLimit = $limit;

        if ($maxTotal !== null) {
            $remaining = $maxTotal - $offset;

            if ($remaining <= 0) {
                return [];
            }

            $effectiveLimit = min($limit, $remaining);
        }

        return $this->createQueryBuilder('p')
            ->orderBy('p.id', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($effectiveLimit)
            ->getQuery()
            ->getResult();
    }
}
