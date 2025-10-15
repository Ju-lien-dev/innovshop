<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function sumTotalAll(): float
    {
        // SUM(DECIMAL) renvoie une string => on caste proprement
        $sum = $this->createQueryBuilder('c')
            ->select('COALESCE(SUM(c.total), 0)')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $sum;
    }

    public function sumTotalSince(\DateTimeInterface $since): float
    {
        $sum = $this->createQueryBuilder('c')
            ->select('COALESCE(SUM(c.total), 0)')
            ->where('c.createdAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $sum;
    }

    /**
     * Retourne un tableau comme:
     * [
     *   ['status' => 'paid', 'nb' => 12],
     *   ['status' => 'shipped', 'nb' => 3],
     *   ...
     * ]
     */
    public function countByStatus(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.status AS status, COUNT(c.id) AS nb')
            ->groupBy('c.status')
            ->orderBy('nb', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Dernières commandes avec quelques champs utiles.
     */
    public function findLatest(int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')->addSelect('u')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findSinceWithStatuses(\DateTimeImmutable $since, array $statuses): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.createdAt >= :since')
            ->andWhere('c.status IN (:statuses)')
            ->setParameter('since', $since)
            ->setParameter('statuses', $statuses)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
