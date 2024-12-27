<?php

// src/Repository/LanguageRepository.php

namespace App\AppBundle\Repository;

use App\AppBundle\Entity\Language;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * LanguageRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class LanguageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Language::class);
    }

    /**
     * Returns the number of Language rows in the database.
     *
     * @return int The number of rows
     */
    public function countLanguages(): int
    {
        // Use QueryBuilder to count the rows
        $queryBuilder = $this->createQueryBuilder('l')
                             ->select('count(l)');

        // Return the single scalar result (count)
        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
