<?php

namespace App\Entity;

use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use App\Utility\RoleComparator;
use App\Validator\StudentClass as StudentClassConstraint;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    /**
     * The fundamental roles with their description.
     * The order is important. The roles are
     * ordered from lowest to highest roles.
     */
    const ROLES = [
        "ROLE_OTHER" => "neznámý",
        "ROLE_STUDENT" => "student",
        "ROLE_TEACHER" => "učitel",
        "ROLE_ADMIN" => "admin",
        "ROLE_SUPERADMIN" => "superadmin",
    ];
    const STUDENT_ROLES = ['ROLE_STUDENT'];
    const TEACHER_ROLES = ['ROLE_TEACHER', 'ROLE_ADMIN', 'ROLE_SUPERADMIN'];
    const ADMIN_ROLES = ['ROLE_ADMIN', 'ROLE_SUPERADMIN'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255, unique: true)]
    private string $username;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $originalRole;
    
    #[ORM\Column(length: 16, nullable: true)]
    private ?string $originalStudentClass = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $effectiveRole = null;

    #[Assert\When(
        expression: 'this.getEffectiveRole() == "ROLE_STUDENT"',
        constraints: [
            new Assert\NotNull(message:"Třída musí být vyplněna."),
            new StudentClassConstraint(message:"Neplatný název třídy."),
        ],
    )]
    #[ORM\Column(length: 16, nullable: true)]
    private ?string $effectiveStudentClass = null;
    
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $restorableRole = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $lastLoginAt;

    /**
     * @var Collection<int, Assignment>
     */
    #[ORM\OneToMany(targetEntity: Assignment::class, mappedBy: 'owner')]
    private Collection $ownedAssignments;

    public function __construct(string $username)
    {
        $this->username = $username;
        $this->ownedAssignments = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username ?? null;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name ?? null;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getOriginalRole(): ?string
    {
        return $this->originalRole ?? null;
    }

    public function setOriginalRole(string $originalRole): static
    {
        $this->originalRole = $originalRole;

        return $this;
    }

    public function getOriginalStudentClass(): ?string
    {
        return $this->originalStudentClass ?? null;
    }

    public function setOriginalStudentClass(?string $originalStudentClass): static
    {
        $this->originalStudentClass = $originalStudentClass;

        return $this;
    }

    public function getEffectiveRole(): ?string
    {
        return $this->effectiveRole ?? null;
    }

    public function setEffectiveRole(?string $effectiveRole): static
    {
        $this->effectiveRole = $effectiveRole;

        return $this;
    }

    public function getEffectiveStudentClass(): ?string
    {
        return $this->effectiveStudentClass ?? null;
    }

    public function setEffectiveStudentClass(?string $effectiveStudentClass): static
    {
        $this->effectiveStudentClass = $effectiveStudentClass;

        return $this;
    }

    public function getRestorableRole(): ?string
    {
        return $this->restorableRole ?? null;
    }

    public function setRestorableRole(?string $restorableRole): static
    {
        $this->restorableRole = $restorableRole;

        return $this;
    }

    public function getRealRole(): string
    {
        return $this->getEffectiveRole() ?? $this->getOriginalRole();
    }

    public function getRealStudentClass(): ?string
    {
        if ($this->getRealRole() !== 'ROLE_STUDENT') {
            return null;
        }
        return $this->getEffectiveStudentClass() ?? ($this->getOriginalStudentClass() ?? '?');
    }

    public function getLastLoginAt(): ?DateTimeImmutable
    {
        return $this->lastLoginAt ?? null;
    }

    public function setLastLoginAt(?DateTimeImmutable $lastLoginAt): self
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    public function getFundamentalRoles(): array
    {
        return [$this->getRealRole()];
    }

    public function getFundamentalRoleGains(): array
    {
        return [
            [$this->getOriginalRole(), $this->getRealRole()],
        ];
    }

    public function isRoleRestorable(): bool
    {
        return ($this->restorableRole !== null) ? true : false;
    }

    public function restoreRole(): self
    {
        if ($this->isRoleRestorable()) {
            $gainedRole = RoleComparator::max($this->restorableRole, $this->getRealRole());
            if ($this->originalRole === $gainedRole) {
                $this->setEffectiveRole(null);
            } else {
                $this->setEffectiveRole($gainedRole);
            }
            $this->restorableRole = null;
        }
        return $this;
    }

    /**
     * @return Collection<int, Assignment>
     */
    public function getOwnedAssignments(): Collection
    {
        return $this->ownedAssignments;
    }

    public function isStudent(): bool
    {
        return in_array($this->getRealRole(), self::STUDENT_ROLES);
    }

    public function isTeacher(): bool
    {
        return in_array($this->getRealRole(), self::TEACHER_ROLES);
    }

    public function isAdmin(): bool
    {
        return in_array($this->getRealRole(), self::ADMIN_ROLES);
    }

    public function getManifest(): array
    {
        $manifest = [
            "id" => $this->getId(),
            "name" => $this->getName(),
            "role" => strtolower(preg_replace('/^ROLE_/', '', $this->getRealRole())),
        ];
        if ($this->isStudent()) {
            $manifest['class'] = $this->getRealStudentClass();
        }
        return $manifest;
    }
}
