<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route("/", name: "main")]
    public function index(): Response
    {
        $menu = $this->getMenu();

        if (empty($menu)) {
            return $this->render('main.html.twig');
        }

        $mainMenuItem = array_shift($menu);

        return $this->redirectToRoute($mainMenuItem['route']);
    }

    #[Route("/ping", name: "ping")]
    public function ping(Request $request): Response
    {
        return $this->render('ping.html.twig', [
            "selfAddress" => $request->getUri(),
            "remoteAddress" => $request->getClientIp()
        ]);
    }
}
