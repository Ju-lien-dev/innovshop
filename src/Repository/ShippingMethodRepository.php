<?php

namespace App\Repository;

use App\Entity\ShippingMethod;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShippingMethod>
 */
class ShippingMethodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShippingMethod::class);
    }

    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.isActive = :a')->setParameter('a', true)
            ->orderBy('s.amountCents', 'ASC')
            ->getQuery()->getResult();
    }
}
