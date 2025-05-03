<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as AbstractControllerBase;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\ORM\EntityManagerInterface as EntityManager;
use SPSOstrov\SSOBundle\SSOUser;
use App\Framework\MenuGenerator;
use App\Utility\Form;

class AbstractController extends AbstractControllerBase
{
    private MenuGenerator $menuGenerator;
    private RequestStack $requestStack;
    private EntityManager $entityManager;
    private array $modulesEnabled = [];

    public function setServices(
        MenuGenerator $menuGenerator,
        RequestStack $requestStack,
        EntityManager $entityManager
    ): self {
        $this->menuGenerator = $menuGenerator;
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
        return $this;
    }

    protected function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    protected function getRequest(): Request
    {
        $request = $this->requestStack->getCurrentRequest();
        assert($request instanceof Request);
        return $request;
    }

    protected function enableModule(string $moduleName): self
    {
        $this->modulesEnabled[$moduleName] = true;
        return $this;
    }

    protected function disableModule(string $moduleName): self
    {
        unset($this->modulesEnabled[$moduleName]);
        return $this;
    }

    protected function getDefaultParameters(): array
    {
        return [
            "jsLoadModules" => empty($this->modulesEnabled) ? null : implode(" ", array_keys($this->modulesEnabled)),
            "menu" => $this->menuGenerator->generateMenu(),
            "user" => $this->getUser(),
        ];
    }

    protected function renderView(string $view, array $parameters = []): string
    {
        $parameters = array_merge($this->getDefaultParameters(), $parameters);
        return parent::renderView($view, $parameters);
    }

    protected function render(
        string $view,
        array $parameters = [],
        ?Response $response = null
    ): Response {
        $parameters = array_merge($this->getDefaultParameters(), $parameters);
        return parent::render($view, $parameters, $response);
    }

    protected function form(string $form, mixed $data, array $options = []): Form
    {
        $form = $this->createForm($form, $data, $options);
        return new Form(
            $form,
            $this->getRequest(),
            fn (string $template, array $templateVars = []) => $this->render($template, $templateVars)
        );
    }

    protected function generateUrl(
        string $route,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        if (isset($parameters['_back']) && $parameters['_back'] === true) {
            $parameters['_back'] = $this->getRequest()->getRequestUri();
        }
        return parent::generateUrl($route, $parameters, $referenceType);
    }

    protected function redirectBack(bool $always = false): ?Response
    {
        $back = $this->getRequest()->query->get("_back");
        if (is_string($back)) {
            return $this->redirect($back);
        }
        if ($always) {
            return $this->redirect($this->getDefaultBackUrl());
        }
        return null;
    }

    protected function getDefaultBackUrl(): string
    {
        return $this->generateUrl("main");
    }

    public function getUser(): ?SSOUser
    {
        $user = parent::getUser();
        assert($user === null || $user instanceof SSOUser);
        return $user;
    }
}
