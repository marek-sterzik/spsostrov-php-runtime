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

    public function updateCurrentFor(Submission $submission): void
    {
        $assignmentId = $submission->getAssignment()->getId();
        $submissionId = $submission->getId();
        $userId = $submission->getSubmitter()->getId();
        $this->createQueryBuilder("s")
            ->update()
            ->set("s.isCurrent", ":false")
            ->andWhere("s.assignment = :assignment")
            ->andWhere("s.submitter = :user")
            ->andWhere("s.id != :submission")
            ->andWhere("s.state != :draft")
            ->andWhere("s.isCurrent = :true")
            ->setParameter(":false", false)
            ->setParameter(":true", true)
            ->setParameter(":assignment", $assignmentId)
            ->setParameter(":user", $userId)
            ->setParameter(":submission", $submissionId)
            ->setParameter(":draft", SubmissionState::Draft)
            ->getQuery()
            ->execute()
        ;
    }

    public function selectCurrentFor(Submission $submission): array
    {
        $assignmentId = $submission->getAssignment()->getId();
        $submissionId = $submission->getId();
        $userId = $submission->getSubmitter()->getId();
        return $this->createQueryBuilder("s")
            ->andWhere("s.assignment = :assignment")
            ->andWhere("s.submitter = :user")
            ->andWhere("s.id != :submission")
            ->andWhere("s.state != :draft")
            ->andWhere("s.isCurrent = :true")
            ->setParameter(":true", true)
            ->setParameter(":assignment", $assignmentId)
            ->setParameter(":user", $userId)
            ->setParameter(":submission", $submissionId)
            ->setParameter(":draft", SubmissionState::Draft)
            ->getQuery()
            ->getResult()
        ;
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
            ->andWhere('s.state != :trash')
            ->setParameter(':trash', SubmissionState::Trash)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function getLastSubmission(Assignment $assignment, User $submitter): ?Submission
    {
        if (!$assignment->getState()->submissionsAvailable()) {
            return null;
        }
        return $this->createQueryBuilder('s')
            ->andWhere('s.assignment = :assignment')
            ->setParameter(':assignment', $assignment->getId())
            ->andWhere('s.submitter = :submitter')
            ->setParameter(':submitter', $submitter->getId())
            ->addOrderBy('s.id', 'DESC')
            ->andWhere('s.state != :trash')
            ->setParameter(':trash', SubmissionState::Trash)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
