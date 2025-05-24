<?php

namespace App\Repository;

use DateTimeImmutable;
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
            ->andWhere("s.state NOT IN (:drafts)")
            ->andWhere("s.state != :locked")
            ->andWhere("s.isCurrent = :true")
            ->setParameter(":false", false)
            ->setParameter(":true", true)
            ->setParameter(":assignment", $assignmentId)
            ->setParameter(":user", $userId)
            ->setParameter(":submission", $submissionId)
            ->setParameter(":drafts", SubmissionState::drafts())
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
            ->andWhere("s.state NOT IN (:drafts)")
            ->andWhere("s.isCurrent = :true")
            ->setParameter(":true", true)
            ->setParameter(":assignment", $assignmentId)
            ->setParameter(":user", $userId)
            ->setParameter(":submission", $submissionId)
            ->setParameter(":drafts", SubmissionState::drafts())
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
            ->andWhere('s.state NOT IN (:draftOrTrash)')
            ->setParameter(':draftOrTrash', SubmissionState::draftsAndTrash())
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

    public function getEarlierSubmission(Submission $submission): ?Submission
    {
        if (!$submission->getAssignment()->getState()->submissionsAvailable()) {
            return null;
        }
        $assignmentId = $submission->getAssignment()->getId();
        $submitterId = $submission->getSubmitter()->getId();
        $submissionId = $submission->getId();
        $timestamp = $submission->getSubmittedAt() ?? (new DateTimeImmutable());
        return $this->createQueryBuilder('s')
            ->andWhere('s.assignment = :assignment')
            ->setParameter(':assignment', $assignmentId)
            ->andWhere('s.submitter = :submitter')
            ->setParameter(':submitter', $submitterId)
            ->andWhere('s.state NOT IN (:draftsAndTrash)')
            ->setParameter(':draftsAndTrash', SubmissionState::draftsAndTrash())
            ->andWhere('s.submittedAt <= :timestamp')
            ->setParameter(':timestamp', $timestamp)
            ->andWhere('s.id != :submission')
            ->setParameter(":submission", $submissionId)
            ->addOrderBy('s.submittedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
