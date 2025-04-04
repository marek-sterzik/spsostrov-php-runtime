<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Form\FormInterface;
use Doctrine\ORM\QueryBuilder;
use App\Entity\Assignment;
use App\Utility\Cell;
use App\Utility\Action;
use App\Utility\SearchTool;
use App\Form\Filter\StudentAssignmentsType;

class StudentsAssignmentsController extends AbstractDbTableController
{
    #[IsGranted('ROLE_STUDENT')]
    #[Route("/submit", name: "submit")]
    public function index(): Response
    {
        return $this->renderTable();
    }

    protected function getBaseQueryBuilder(array $filterData): QueryBuilder
    {
        $me = $this->getUser()->getUserData();
        $qb = $this->getEntityManager()->getRepository(Assignment::class)->createQueryBuilder('a');
                $searchTool = new SearchTool();
        $searchTool->handle(null, function (QueryBuilder $qb, string $string, ?string $type, string $var) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like("a.caption", ":${var}"),
                $qb->expr()->like("a.description", ":${var}"),
            ));
            $qb->setParameter(":${var}", "%$string%");
        });
        $searchTool->search($qb, $filterData['q'] ?? '');
        return $qb;
    }

    protected function getHeader(array $filterData): array
    {
        return [
            "assignment" => null,
        ];
    }

    protected function recordToArray(mixed $assignment): array
    {
        assert($assignment instanceof Assignment);
        return [
            "assignment" => $this->getAssignmentCell($assignment),
            "_actions" => $this->getAssignmentActions($assignment),
        ];
    }

    private function getAssignmentCell(Assignment $assignment): mixed
    {
        $content = $this->renderView(
            "snippets/student-assignment.html.twig",
            ["assignment" => $assignment]
        );
        return Cell::html($content);
    }

    private function getAssignmentActions(Assignment $assignment): array
    {
        return [];
        /*
        $actions = [
            Action::get(
                $this->generateUrl("user", ["user" => $user->getId(), "_back" => true]),
                "nastavit roli",
                "btn-primary"
            )
        ];
        if ($user->isRoleRestorable()) {
            array_unshift($actions, Action::get(
                $this->generateUrl("restore_role_user", ["user" => $user->getId(), "_back" => true]),
                "obnovit roli",
                "btn-danger me-2"
            ));
        }
        return $actions;
        */
    }

    protected function getForm(array $formData): ?FormInterface
    {
        return $this->createForm(StudentAssignmentsType::class, $formData);
    }

    protected function getDefaultFilterData(): array
    {
        return [];
    }
}
