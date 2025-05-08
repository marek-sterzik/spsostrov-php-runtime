<?php

namespace App\Framework;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\RequestStack;
use SPSOstrov\SSOBundle\SSOUserDataProviderInterface;
use SPSOstrov\SSOBundle\SSORoleDeciderInterface;
use SPSOstrov\SSOBundle\SSOUser;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface as EntityManager;
use App\Entity\User;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MenuGenerator
{
    private array $menuTemplate;
    private ?array $menu = null;

    public function __construct(
        private RequestStack $requestStack,
        private AuthorizationCheckerInterface $authorizationChecker,
        string $menuFile
    ) {
        $this->menuTemplate = Yaml::parseFile($menuFile)['menu'];
    }

    public function generateMenu(): array
    {
        $currentRoute = $this->requestStack->getCurrentRequest()->attributes->get('_route');
        if ($this->menu === null) {
            $this->menu = [];
            foreach ($this->menuTemplate as $menuItem) {
                if ($this->granted($menuItem['roles'] ?? null)) {
                    $finalItem = $this->createMenuItem($menuItem, $currentRoute);
                    if (!$finalItem['hidden'] || $finalItem['actual']) {
                        $this->menu[] = $finalItem;
                    }
                }
            }
        }
        return $this->menu;
    }

    private function createMenuItem(array $menuItem, string $currentRoute): array
    {
        $menuItem['hidden'] = $menuItem['hidden'] ?? false;
        $menuItem['target_blank'] = $menuItem['target_blank'] ?? false;
        $menuItem['actual'] = $this->isMenuItemActual($menuItem, $currentRoute);

        return $menuItem;
    }

    private function isMenuItemActual(array $menuItem, string $currentRoute): bool
    {
        if ($menuItem['route'] === $currentRoute) {
            return true;
        }
        if (in_array($currentRoute, $menuItem['routes'] ?? [])) {
            return true;
        }
        return false;
    }

    private function granted(?array $roles): bool
    {
        $roles = $roles ?? ['ROLE_USER'];
        foreach ($roles as $role) {
            if ($this->authorizationChecker->isGranted($role)) {
                return true;
            }
        }
        return false;
    }
}
