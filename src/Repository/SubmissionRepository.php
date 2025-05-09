<?php

namespace App\Repository;

use App\Entity\Assignment;
use App\Entity\User;
use App\Entity\Submission;
use App\Submission\SubmissionState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Submission>
 */
class SubmissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Submission::class);
    }

    public function countSubmissions(Assignment $assignment): ?int
    {
        if (!$assignment->getState()->submissionsAvailable()) {
            return null;
        }
        $qb = $this->createQueryBuilder('s');
        return $qb
            ->select($qb->expr()->countDistinct('s.submitter'))
            ->andWhere('s.assignment = :assignment')
            ->setParameter(':assignment', $assignment->getId())
            ->andWhere('s.state != :draft')
            ->setParameter(':draft', SubmissionState::Draft)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
