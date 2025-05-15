<?php

namespace App\Entity;

use DateInterval;
use DateTimeImmutable;
use App\Repository\AssignmentRepository;
use App\Assignment\AssignmentState;
use App\Assignment\SubmissionMode;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Validator\StudentClassPattern;
use App\Validator\DeadlineInSchoolYear;
use App\Utility\CurrentSchoolYear;

#[ORM\Entity(repositoryClass: AssignmentRepository::class)]
#[ORM\Index(name: 'main_order_created_at_index', fields: ['mainOrder', 'createdAt'])]
#[ORM\Index(name: 'state_hard_deadline_index', fields: ['state', 'hardDeadline'])]
#[ORM\Index(name: 'school_year_state', fields: ['schoolYear', 'state'])]
#[DeadlineInSchoolYear]
class Assignment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255)]
    private ?string $caption = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[StudentClassPattern(message: "neplatný seznam tříd")]
    #[ORM\Column(length: 255)]
    private ?string $classes = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $classesRegexp = null;

    #[ORM\Column(nullable: true)]
    private ?int $schoolYear = null;

    #[ORM\Column]
    private bool $public = false;
    
    #[ORM\Column(type: Types::STRING, length: 16, enumType: SubmissionMode::class)]
    private SubmissionMode $submissionMode = SubmissionMode::MultipleTimes;
    
    #[ORM\Column]
    private bool $backedUp = false;

    #[ORM\Column(type: Types::STRING, length: 16, enumType: AssignmentState::class)]
    private AssignmentState $state = AssignmentState::Draft;
    
    #[ORM\ManyToOne(inversedBy: 'ownedAssignments')]
    #[ORM\JoinColumn(nullable: false)]
    private User $owner;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $softDeadline = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $hardDeadline = null;

    #[ORM\Column]
    private int $mainOrder = 0;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;
    
    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $activatedAt = null;

    /**
     * @var Collection<int, Submission>
     */
    #[ORM\OneToMany(targetEntity: Submission::class, mappedBy: 'assignment', orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy(["id" => "DESC"])]
    private Collection $submissions;

    public function __construct(User $owner)
    {
        $this->owner = $owner;
        $this->createdAt = new DateTimeImmutable();
        $this->updateMainOrder();
        $this->submissions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function setCaption(string $caption): static
    {
        $this->caption = $caption;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getClasses(): ?string
    {
        return $this->classes;
    }

    public function setClasses(string $classes): static
    {
        $this->classes = $classes;

        return $this;
    }

    public function getClassesRegexp(): ?string
    {
        return $this->classesRegexp;
    }

    public function setClassesRegexp(string $classesRegexp): self
    {
        $this->classesRegexp = $classesRegexp;

        return $this;
    }

    public function getSchoolYear(): ?int
    {
        if ($this->state === AssignmentState::Draft) {
            $currentSchoolYear = CurrentSchoolYear::get();
            if ($this->schoolYear < $currentSchoolYear) {
                $offset = $currentSchoolYear - $this->schoolYear;
                $this->schoolYear = $currentSchoolYear;
                $interval = new DateInterval("P" . $offset . "Y");
                if ($this->softDeadline !== null) {
                    $this->softDeadline = $this->softDeadline->add($interval);
                }
                if ($this->hardDeadline !== null) {
                    $this->hardDeadline = $this->hardDeadline->add($interval);
                }
            }
        }
        return $this->schoolYear;
    }

    public function setSchoolYear(?int $schoolYear): static
    {
        $this->schoolYear = $schoolYear;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): static
    {
        $this->public = $public;

        return $this;
    }

    public function getSubmissionMode(): SubmissionMode
    {
        return $this->submissionMode;
    }

    public function setSubmissionMode(SubmissionMode $submissionMode): self
    {
        $this->submissionMode = $submissionMode;

        return $this;
    }

    public function isBackedUp(): bool
    {
        return $this->backedUp;
    }

    public function setBackedUp(bool $backedUp): self
    {
        $this->backedUp = $backedUp;

        return $this;
    }
    
    public function getState(): AssignmentState
    {
        if ($this->state === AssignmentState::Active && $this->isAfterDeadline()) {
            $this->state = AssignmentState::Finished;
            $this->updateMainOrder();
        }
        if ($this->schoolYear < CurrentSchoolYear::get() && $this->state !== AssignmentState::Draft) {
            $this->state = AssignmentState::Archived;
            $this->updateMainOrder();
        }
        return $this->state;
    }

    public function setState(AssignmentState $state): static
    {
        $this->getSchoolYear(); //Just calling this because of the side effect fixing the school year
        if ($state === AssignmentState::Active && $this->isAfterDeadline()) {
            $state = AssignmentState::Finished;
        }
        $this->state = $state;
        $this->updateMainOrder();
        if ($state === AssignmentState::Active && $this->activatedAt === null) {
            $this->activatedAt = new DateTimeImmutable();
        }

        return $this;
    }

    private function updateMainOrder(): self
    {
        $this->mainOrder = $this->state->getParam("order");
        return $this;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getSoftDeadline(): ?DateTimeImmutable
    {
        return $this->softDeadline;
    }

    public function setSoftDeadline(?DateTimeImmutable $softDeadline): static
    {
        $this->softDeadline = $softDeadline;

        return $this;
    }

    public function getHardDeadline(): ?DateTimeImmutable
    {
        return $this->hardDeadline;
    }

    public function setHardDeadline(?DateTimeImmutable $hardDeadline): static
    {
        $this->hardDeadline = $hardDeadline;

        return $this;
    }

    public function getStudentDeadline(): ?DateTimeImmutable
    {
        return $this->softDeadline ?? $this->hardDeadline;
    }

    public function isStudentDeadlineExceeded(): bool
    {
        $studentDeadline = $this->getStudentDeadline();
        return ($studentDeadline !== null) ? ($studentDeadline < new DateTimeImmutable()) : false;
    }

    public function isStudentDeadlineNear(): bool
    {
        $studentDeadline = $this->getStudentDeadline();
        return ($studentDeadline !== null) ? ($studentDeadline <= new DateTimeImmutable("+10 minutes")) : false;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getActivatedAt(): ?DateTimeImmutable
    {
        return $this->activatedAt;
    }

    public function fillFrom(self $template): self
    {
        $this->setCaption($template->getCaption());
        $this->setDescription($template->getDescription());
        $this->setClasses($template->getClasses());
        $this->setPublic($template->isPublic());
        $this->setBackedUp($template->isBackedUp());

        $schoolYear = $template->getSchoolYear();
        if ($schoolYear !== null && $schoolYear >= CurrentSchoolYear::get()) {
            $this->setSchoolYear($schoolYear);
            $this->setSoftDeadline($template->getSoftDeadline());
            $this->setHardDeadline($template->getHardDeadline());
        }

        return $this;
    }

    public function hasEditRights(?User $user): bool
    {
        if ($user === null) {
            return false;
        }
        if (!$user->isTeacher()) {
            return false;
        }
        if ($this->owner !== $user && !$user->isAdmin()) {
            return false;
        }
        return true;
    }

    public function canBeViewedBy(?User $user): bool
    {
        if ($user === null) {
            return false;
        }
        if (!$user->isTeacher()) {
            return false;
        }
        if ($this->owner === $user || $user->isAdmin()) {
            return true;
        }
        if ($this->public && $this->getState() !== AssignmentState::Draft) {
            return true;
        }
        return false;
    }

    public function canBeEditedBy(?User $user): bool
    {
        if (!$this->hasEditRights($user)) {
            return false;
        }
        return $this->getState()->editAllowed();
    }

    public function canBeDeletedBy(?User $user): bool
    {
        if (!$this->hasEditRights($user)) {
            return false;
        }
        return $this->getState()->deleteAllowed();
    }

    public function canTransitTo(?User $user, AssignmentState $finalState): bool
    {
        if (!$this->hasEditRights($user)) {
            return false;
        }
        if (!$this->getState()->canTransitTo($finalState)) {
            return false;
        }

        if ($finalState === AssignmentState::Active &&
            $this->getState() === AssignmentState::Finished &&
            $this->isAfterDeadline()
        ) {
            return false;
        }
        
        return true;
    }

    public function canSubmit(?User $user): bool
    {
        if ($user === null) {
            return false;
        }
        if ($this->getState() !== AssignmentState::Active) {
            return false;
        }
        if (!$user->isStudent()) {
            return false;
        }
        $studentClass = $user->getRealStudentClass();
        if ($studentClass === null) {
            return false;
        }
        if (!preg_match('/'. $this->getClassesRegexp() .'/', $studentClass)) {
            return false;
        }
        return true;
    }

    private function isAfterDeadline(): bool
    {
        return ($this->hardDeadline !== null && (new DateTimeImmutable()) > $this->hardDeadline);
    }

    /**
     * @return Collection<int, Submission>
     */
    public function getSubmissions(): Collection
    {
        return $this->submissions;
    }

    public function getManifest(): array
    {
        return [
            "id" => $this->getId(),
            "caption" => $this->getCaption(),
            "classes" => $this->getClasses(),
            "schoolYear" => $this->getSchoolYear(),
            "softDeadline" => $this->getSoftDeadline()?->format("Y-m-d H:i:s"),
            "hardDeadline" => $this->getHardDeadline()?->format("Y-m-d H:i:s"),
            "owner" => $this->getOwner()->getManifest(),
            "submissionMode" => $this->getSubmissionMode()->value,
            "public" => $this->isPublic(),
            "backedUp" => $this->isBackedUp(),
            "createdAt" => $this->getCreatedAt()->format("Y-m-d H:i:s"),
            "activatedAt" => $this->getActivatedAt()?->format("Y-m-d H:i:s"),
        ];
    }
}
