<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Form\FormInterface;
use App\Form\Filter\StudentSubmissionsType;
use Doctrine\ORM\QueryBuilder;

class StudentSubmissionsController extends AbstractSubmissionsController
{
    #[IsGranted('ROLE_STUDENT')]
    #[Route("/submissions", name: "submissions")]
    public function index(): Response
    {
        return $this->renderTable();
    }

    protected function getBaseQueryBuilder(array $filterData): QueryBuilder
    {
        $qb = parent::getBaseQueryBuilder($filterData);

        $qb
            ->andWhere("s.submitter = :me")
            ->setParameter(":me", $this->getUserEntity()->getId())
        ;

        return $qb;
    }


    protected function getForm(array $formData): ?FormInterface
    {
        return $this->createForm(StudentSubmissionsType::class, $formData);
    }

    protected function isStudentView(): bool
    {
        return true;
    }
}
