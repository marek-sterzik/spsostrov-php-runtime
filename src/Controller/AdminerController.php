<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminerController extends AbstractController
{
    public function __construct(
        private string $databaseUrl,
        private bool $adminerEnabled,
        private string $adminerBootFile
    ) {
    }

    #[Route("/adminer", name: "app.adminer")]
    public function pageAdminer(): Response
    {
        if (!$this->adminerEnabled) {
            throw new NotFoundHttpException("File not found.");
        }

        return new StreamedResponse(
            function () {
                $httpAuth = null;
                $dbConf = $this->databaseUrl;
                include $this->adminerBootFile;
            }
        );
    }
}
