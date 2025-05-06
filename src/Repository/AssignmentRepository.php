<?php

namespace App\Repository;

use DateTimeImmutable;
use App\Assignment\AssignmentState;
use App\Entity\Assignment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Utility\CurrentSchoolYear;

/**
 * @extends ServiceEntityRepository<Assignment>
 */
class AssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Assignment::class);
    }

    public function updateStates(): void
    {
        $this->createQueryBuilder("a")
            ->update()
            ->set("a.state", ":newState")
            ->setParameter(":newState", AssignmentState::Finished)
            ->andWhere("a.hardDeadline < :now")
            ->setParameter(":now", new DateTimeImmutable())
            ->getQuery()
            ->execute()
        ;

        $this->createQueryBuilder("a")
            ->update()
            ->set("a.state", ":newState")
            ->setParameter(":newState", AssignmentState::Archived)
            ->andWhere("a.schoolYear < :currentSchoolYear")
            ->andWhere("a.state != :draftState")
            ->setParameter(":currentSchoolYear", CurrentSchoolYear::get())
            ->setParameter(":draftState", AssignmentState::Draft)
            ->getQuery()
            ->execute()
        ;
    }
}
