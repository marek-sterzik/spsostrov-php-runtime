<?php

namespace App\Entity;

use DateTimeImmutable;
use App\Submission\SubmissionState;
use App\Repository\SubmissionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Utility\Uuid;

#[ORM\Entity(repositoryClass: SubmissionRepository::class)]
class Submission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::GUID, unique: true)]
    private string $uuid;

    #[ORM\ManyToOne(inversedBy: 'submissions')]
    #[ORM\JoinColumn(nullable: false)]
    private Assignment $assignment;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $submitter;

    #[ORM\Column(type: Types::STRING, length: 16, enumType: SubmissionState::class)]
    private SubmissionState $state = SubmissionState::Draft;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;
    
    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $submittedAt = null;

    public function __construct(Assignment $assignment, User $submitter)
    {
        $this->uuid = Uuid::uuid6()->toString();
        $this->assignment = $assignment;
        $this->submitter = $submitter;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getAssignment(): Assignment
    {
        return $this->assignment;
    }

    public function setAssignment(Assignment $assignment): static
    {
        $this->assignment = $assignment;

        return $this;
    }

    public function getSubmitter(): ?User
    {
        return $this->submitter;
    }

    public function setSubmitter(User $submitter): static
    {
        $this->submitter = $submitter;

        return $this;
    }

    public function getState(): SubmissionState
    {
        return $this->state;
    }

    public function setState(SubmissionState $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getSubmittedAt(): ?DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(?DateTimeImmutable $submittedAt): static
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    public function getManifest(): array
    {
        return [
            "id" => $this->getId(),
            "uuid" => $this->getUuid(),
            "assignment" => $this->getAssignment()->getManifest(),
            "submitter" => $this->getSubmitter()->getManifest(),
            "createdAt" => $this->getCreatedAt()?->format("Y-m-d H:i:s"),
            "submittedAt" => $this->getSubmittedAt()?->format("Y-m-d H:i:s"),
        ];
    }
}
