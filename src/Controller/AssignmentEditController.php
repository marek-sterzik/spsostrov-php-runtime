<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Assignment;
use App\Form\AssignmentEditType;
use App\Utility\RoleComparator;
use App\StudentClass\StudentClass;
use App\Assignment\AssignmentState;
use App\Utility\CurrentSchoolYear;
use App\Markdown\Markdown;

class AssignmentEditController extends AbstractController
{
    public function __construct(private StudentClass $studentClass, private Markdown $markdown)
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
    public function newAssignment(Request $request): Response
    {
        $template = $request->query->get("template");
        if (is_string($template)) {
            if (preg_match('/^[0-9]+$/', $template)) {
                $template = $this->getEntityManager()->getRepository(Assignment::class)->find((int)$template);
            } else {
                $template = null;
            }
            if ($template === null || !$template->canBeViewedBy($this->getUserEntity())) {
                return $this->redirectBack(true);
            }
        } else {
            $template = null;
        }
        $assignment = new Assignment($this->getUserEntity());
        if ($template !== null) {
            $schoolYear = CurrentSchoolYear::get();
            $assignment->fillFrom($template, $schoolYear, $schoolYear + 1);
        }
        $this->getEntityManager()->persist($assignment);
        return $this->editAssignment($assignment, true);
    }

    #[IsGranted('ROLE_TEACHER')]
    #[Route("/assignment/{assignment}/delete", name: "assignment-delete")]
    public function deleteAssignment(Assignment $assignment): Response
    {
        if ($assignment->canBeDeletedBy($this->getUserEntity())) {
            $this->getEntityManager()->remove($assignment);
            $this->markdown->invalidate($assignment);
            $this->getEntityManager()->flush();
        }
        return $this->redirectBack(true);
    }

    #[IsGranted('ROLE_TEACHER')]
    #[Route("/assignment/{assignment}/set-state", name: "assignment-set-state")]
    public function setStateAssignment(Assignment $assignment, Request $request): Response
    {
        $state = $request->query->get("state");
        if (is_string($state)) {
            $state = AssignmentState::tryFrom($state);
        } else {
            $state = null;
        }
        if ($state !== null && $assignment->canTransitTo($this->getUserEntity(), $state)) {
            $assignment->setState($state);
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
            $caption = "Nové zadání";
        } else {
            $caption = "Upravit zadání";
        }

        return $this->form(AssignmentEditType::class, $assignment)
            ->action($new ? "Vytvořit" : "Uložit", function (Assignment $assignment) {
                $parsed = $this->studentClass->parseStudentClassPattern($assignment->getClasses());
                $assignment->setClasses($parsed['normalized'] ?? $assignment->getClasses());
                $assignment->setClassesRegexp($parsed['regexp']);
                $this->getEntityManager()->flush();
                $this->markdown->refresh($assignment);
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
