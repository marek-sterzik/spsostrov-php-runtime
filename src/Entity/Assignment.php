<?php

namespace App\Entity;

use DateTimeImmutable;
use App\Repository\AssignmentRepository;
use App\Assignment\AssignmentState;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Validator\StudentClassPattern;

#[ORM\Entity(repositoryClass: AssignmentRepository::class)]
#[ORM\Index(name: 'main_order_created_at_index', fields: ['mainOrder', 'createdAt'])]
#[ORM\Index(name: 'state_hard_deadline_index', fields: ['state', 'hardDeadline'])]
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
        return $this->state;
    }

    public function setState(AssignmentState $state): static
    {
        if ($state === AssignmentState::Active && $this->isAfterDeadline()) {
            $state = AssignmentState::Finished;
        }
        $this->state = $state;
        if ($state === AssignmentState::Active && $this->activatedAt === null) {
            $this->activatedAt = new DateTimeImmutable();
        }
        $this->mainOrder = $state->getParam("order");

        return $this;
    }

    public function updateState(): bool
    {
        $oldState = $this->state;
        $this->setState($this->state);
        return $this->state !== $oldState;
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

    public function fillFrom(
        self $template,
        ?int $allowedSchoolYearMin = null,
        ?int $allowedSchoolYearMax = null
    ): self
    {
        $this->setCaption($template->getCaption());
        $this->setDescription($template->getDescription());
        $this->setClasses($template->getClasses());
        $this->setPublic($template->isPublic());

        $schoolYear = $template->getSchoolYear();
        if (($allowedSchoolYearMin === null || $schoolYear >= $allowedSchoolYearMin) &&
            ($allowedSchoolYearMax === null || $schoolYear <= $allowedSchoolYearMax)
        ) {
            $this->setSchoolYear($schoolYear);
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
        if ($this->public && $this->state !== AssignmentState::Draft) {
            return true;
        }
        return false;
    }

    public function canBeEditedBy(?User $user): bool
    {
        if (!$this->hasEditRights($user)) {
            return false;
        }
        return $this->state->editAllowed();
    }

    public function canBeDeletedBy(?User $user): bool
    {
        if (!$this->hasEditRights($user)) {
            return false;
        }
        return $this->state->deleteAllowed();
    }

    public function canTransitTo(?User $user, AssignmentState $finalState): bool
    {
        if (!$this->hasEditRights($user)) {
            return false;
        }
        if (!$this->state->canTransitTo($finalState)) {
            return false;
        }

        if ($finalState === AssignmentState::Active &&
            $this->state === AssignmentState::Finished &&
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
