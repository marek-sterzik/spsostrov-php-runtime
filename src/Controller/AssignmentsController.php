<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Form\FormInterface;
use Doctrine\ORM\QueryBuilder;
use App\Entity\Assignment;
use App\Component\Cell;
use App\Component\Action;
use App\Component\MultiAction;
use App\SearchTool\SearchTool;
use App\Form\Filter\AssignmentsType;
use App\Assignment\AssignmentActions;
use App\Assignment\AssignmentState;
use App\Component\StudentClassPattern as StudentClassPatternComponent;
use App\Repository\SubmissionRepository;

class AssignmentsController extends AbstractDbTableController
{
    public function __construct(
        private AssignmentActions $assignmentActions,
        private SubmissionRepository $submissionRepository
    ) {
    }

    #[IsGranted('ROLE_TEACHER')]
    #[Route("/assignments", name: "assignments")]
    public function index(): Response
    {
        $this->getEntityManager()->getRepository(Assignment::class)->updateStates();
        return $this->renderTable();
    }

    protected function getBaseQueryBuilder(array $filterData): QueryBuilder
    {
        $adminView = $this->isGranted("ROLE_ADMIN");
        $me = $this->getUserEntity();
        $qb = $this->getEntityManager()->getRepository(Assignment::class)->createQueryBuilder('a');
        if ($filterData['a']) {
            if (!$adminView) {
                $qb->andWhere($qb->expr()->orX(
                    $qb->expr()->eq("a.owner", ":owner"),
                    $qb->expr()->andX(
                        $qb->expr()->neq("a.state", ":notState"),
                        $qb->expr()->eq("a.public", true),
                    )
                ));
                $qb->setParameter(":owner", $me);
                $qb->setParameter(":notState", AssignmentState::Draft);
            }
        } else {
            $qb->andWhere("a.owner = :owner");
            $qb->setParameter(":owner", $me);
        }
        if (!$filterData['d']) {
            $qb->andWhere("a.state != :archived");
            $qb->setParameter(":archived", AssignmentState::Archived);
        }
        $searchTool = new SearchTool();
        $searchTool->handle(null, function (QueryBuilder $qb, string $string, ?string $type, string $var) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like("a.caption", ":${var}"),
                $qb->expr()->like("a.description", ":${var}"),
            ));
            $qb->setParameter(":${var}", "%$string%");
        });
        $searchTool->search($qb, $filterData['q'] ?? '');
        $qb->addOrderBy("a.mainOrder", "DESC");
        $qb->addOrderBy("a.createdAt", "DESC");
        return $qb;
    }

    protected function getHeader(array $filterData): array
    {
        return [
            "caption" => "název",
            "studentClass" => "třídy",
            "state" => "stav",
            "type" => "typ",
            "owner" => "vlastník",
            "createdAt" => "vytvořeno",
            "submitted" => "odevzdáno",
        ];
    }

    protected function recordToArray(mixed $assignment): array
    {
        assert($assignment instanceof Assignment);
        $state = $this->renderView('snippets/assignment-state.html.twig', ['state' => $assignment->getState()]);
        $isMe = ($assignment->getOwner() === $this->getUserEntity()) ? true : false;
        $meBadge = $isMe ? (' ' . $this->renderView('snippets/me.html.twig')) : '';
        $type = $this->renderView('snippets/assignment-public.html.twig', ["public" => $assignment->isPublic()]);
        $createdAt = $this->renderView('snippets/datetime.html.twig', ["date" => $assignment->getCreatedAt()]);
        $submissionCount = $this->submissionRepository->countSubmissions($assignment);
        $submitted = $this->renderView('snippets/object-count.html.twig', ["count" => $submissionCount]);

        return [
            "caption" => $assignment->getCaption(),
            "studentClass" => StudentClassPatternComponent::get($assignment->getClasses()),
            "owner" => Cell::html(htmlspecialchars($assignment->getOwner()->getName()) . $meBadge),
            "type" => Cell::html($type),
            "state" => Cell::html($state),
            "createdAt" => Cell::html($createdAt),
            "submitted" => Cell::html($submitted),
            "_actions" => $this->getAssignmentActions($assignment),
        ];
    }

    private function getAssignmentActions(Assignment $assignment): array
    {
        $actions = [];

        $actions = $this->assignmentActions->generate($assignment, true);
        
        return [MultiAction::get(...$actions)];
    }

    protected function getForm(array $formData): ?FormInterface
    {
        return $this->createForm(AssignmentsType::class, $formData, [
            "add_link_url" => $this->generateUrl("new-assignment", ["_back" => true])
        ]);
    }

    protected function getDefaultFilterData(): array
    {
        return [
            "a" => false,
            "d" => false,
        ];
    }
}
