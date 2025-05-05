<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Assignment;
use App\Form\AssignmentEditType;
use App\Utility\RoleComparator;
use App\StudentClass\StudentClass;

class AssignmentEditController extends AbstractController
{
    public function __construct(private StudentClass $studentClass)
    {
    }

    #[IsGranted('ROLE_TEACHER')]
    #[Route("/assignment/{assignment}/edit", name: "assignment")]
    public function index(Assignment $assignment): Response
    {
        return $this->editAssignment($assignment, false);
    }

    #[IsGranted('ROLE_TEACHER')]
    #[Route("/new-assignment", name: "new-assignment")]
    public function newAssignment(): Response
    {
        $assignment = new Assignment($this->getUserEntity());
        $this->getEntityManager()->persist($assignment);
        return $this->editAssignment($assignment, true);
    }

    #[IsGranted('ROLE_TEACHER')]
    #[Route("/assignment/{assignment}/delete", name: "assignment-delete")]
    public function deleteAssignment(Assignment $assignment): Response
    {
        if ($assignment->canBeDeletedBy($this->getUserEntity())) {
            $this->getEntityManager()->remove($assignment);
            $this->getEntityManager()->flush();
        }
        return $this->redirectBack(true);
    }

    private function editAssignment(Assignment $assignment, bool $new): Response
    {
        if (!$assignment->canBeEditedBy($this->getUserEntity())) {
            return $this->redirectBack(true);
        }

        if ($new) {
            $caption = "nové zadání";
        } else {
            $caption = "upravit zadání";
        }

        return $this->form(AssignmentEditType::class, $assignment)
            ->action($new ? "Vytvořit" : "Uložit", function (Assignment $assignment) {
                $parsed = $this->studentClass->parseStudentClassPattern($assignment->getClasses());
                $assignment->setClasses($parsed['normalized'] ?? $assignment->getClasses());
                $this->getEntityManager()->flush();
                return $this->redirectBack(true);
            })
            ->action("Zrušit", function (Assignment $assignment) {
                return $this->redirectBack(true);
            }, type: 'btn-secondary', validated: false)
            ->caption($caption)
            ->handle()
        ;
    }

    protected function getDefaultBackUrl(): string
    {
        return $this->generateUrl("assignments");
    }
}
