<?php

// src/Repository/WithdrawRepository.php

namespace App\AppBundle\Repository;

use App\AppBundle\Entity\Withdraw;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * WithdrawRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class WithdrawRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Withdraw::class);
    }

    /**
     * Returns the number of Withdraw rows.
     *
     * @param array $criteria Optional criteria to filter the count
     * @return int The number of rows
     */
    public function count(array $criteria = []): int
    {
        return (int) $this->countBy($criteria);
    }

    /**
     * Count Withdraw entities based on given criteria.
     *
     * @param array $criteria Criteria to filter the count
     * @return int
     */
    public function countBy(array $criteria): int
    {
        $queryBuilder = $this->createQueryBuilder('w')
                             ->select('count(w)');

        if (!empty($criteria)) {
            foreach ($criteria as $key => $value) {
                $queryBuilder->andWhere("w.$key = :$key")
                             ->setParameter($key, $value);
            }
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
