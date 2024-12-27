<?php

// src/Repository/SupportRepository.php
// src/Repository/SupportRepository.php
namespace App\AppBundle\Repository;

use App\AppBundle\Entity\Support;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SupportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Support::class);
    }

    /**
     * Returns the number of Support rows.
     *
     * @param array $criteria Optional criteria to filter the count
     * @return int The number of rows
     */
    public function count(array $criteria = []): int
    {
        return $this->countBy($criteria);
    }

    /**
     * Count Support entities based on given criteria.
     *
     * @param array $criteria Criteria to filter the count
     * @return int
     */
    public function countBy(array $criteria): int
    {
        $qb = $this->createQueryBuilder('s')
            ->select('count(s)');

        // Apply criteria dynamically using QueryBuilder
        foreach ($criteria as $key => $value) {
            $qb->andWhere("s.$key = :$key")
               ->setParameter($key, $value);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}

