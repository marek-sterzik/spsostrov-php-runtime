<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function countUsers(): int
    {
        return $this->createQueryBuilder('u')
            ->select('count(1)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findAllWithoutSurnameGuess(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.guessedSurname IS NULL')
            ->getQuery()
            ->getResult()
        ;
    }
}
