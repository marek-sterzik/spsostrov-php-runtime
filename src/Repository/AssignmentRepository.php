<?php

namespace App\Repository;

use DateTimeImmutable;
use App\Assignment\AssignmentState;
use App\Entity\Assignment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Assignment>
 */
class AssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Assignment::class);
    }

    public function updateStates()
    {
        $this->createQueryBuilder("a")
            ->update()
            ->set("a.state", ":newState")
            ->setParameter(":newState", AssignmentState::Finished)
            ->where("a.hardDeadline < :now")
            ->setParameter(":now", new DateTimeImmutable())
            ->getQuery()
            ->execute()
        ;
    }
}
