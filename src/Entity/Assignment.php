<?php

namespace App\Entity;

use DateInterval;
use DateTimeImmutable;
use App\Repository\AssignmentRepository;
use App\Assignment\AssignmentState;
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

    #[ORM\Column(type: Types::STRING, length: 255, enumType: AssignmentState::class)]
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

    public function __construct(User $owner)
    {
        $this->owner = $owner;
        $this->createdAt = new DateTimeImmutable();
        $this->updateMainOrder();
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

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getActivatedAt(): DateTimeImmutable
    {
        return $this->activatedAt;
    }

    public function fillFrom(self $template): self
    {
        $this->setCaption($template->getCaption());
        $this->setDescription($template->getDescription());
        $this->setClasses($template->getClasses());
        $this->setPublic($template->isPublic());

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

    private function isAfterDeadline(): bool
    {
        return ($this->hardDeadline !== null && (new DateTimeImmutable()) > $this->hardDeadline);
    }
}
