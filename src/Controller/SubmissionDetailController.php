<?php

namespace App\Controller;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use App\FileManager\FileManager;
use App\Entity\Submission;

class SubmissionDetailController extends AbstractController
{
    public function __construct(private FileManager $fileManager)
    {
    }

    #[IsGranted('ROLE_STUDENT')]
    #[Route("/submission/show/{submission}", name: 'submission-detail')]
    public function index(Submission $submission): Response
    {
        if (!$submission->canBeViewedBy($this->getUserEntity())) {
            return $this->redirectBack(true);
        }
        return $this->render("submission.html.twig", [
            "submission" => $submission,
            "files" => $this->fileManager->listFiles($submission),
        ]);
    }
}
