<?php

namespace App\Controller;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use App\Entity\Submission;

class SubmissionDetailController extends AbstractController
{
    #[IsGranted('ROLE_STUDENT')]
    #[Route("/submission/show/{submission}", name: 'submission-detail')]
    public function index(Submission $submission): Response
    {
        if ($this->getUserEntity() !== $submission->getSubmitter()) {
            return $this->redirectBack(true);
        }
        return $this->redirectBack(true);
    }
}
