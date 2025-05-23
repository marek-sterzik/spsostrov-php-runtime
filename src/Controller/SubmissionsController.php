<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\QueryBuilder;
use App\Assignment\AssignmentState;
use App\Submission\SubmissionState;

class SubmissionsController extends AbstractSubmissionsController
{
    #[IsGranted('ROLE_TEACHER')]
    #[Route("/submissions", name: "all-submissions")]
    public function index(): Response
    {
        return $this->renderTable();
    }

    protected function backLinkEnabled(): bool
    {
        return true;
    }

    protected function getBaseQueryBuilder(array $filterData): QueryBuilder
    {
        $qb = parent::getBaseQueryBuilder($filterData);
        $adminView = $this->isGranted("ROLE_ADMIN");
        $me = $this->getUserEntity();
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
        $qb->andWhere("s.state NOT IN (:drafts)");
        $qb->setParameter(":drafts", SubmissionState::drafts());

        return $qb;
    }

    protected function isStudentView(): bool
    {
        return false;
    }
}
