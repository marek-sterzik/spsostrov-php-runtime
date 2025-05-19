<?php

namespace App\Framework;

use Exception;
use Symfony\Bundle\FrameworkBundle\Routing\Router as SymfonyRouter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouteCollection;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
class Router implements WarmableInterface, RouterInterface, RequestMatcherInterface
{
    public function __construct(
        private SymfonyRouter $router,
        private RequestStack $requestStack
    ) {
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->router->getRouteCollection();
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        return $this->router->warmUp($cacheDir, $buildDir);
    }

    public function setContext(RequestContext $context): void
    {
        $this->router->setContext($context);
    }

    public function getContext(): RequestContext
    {
        return $this->router->getContext();
    }

    public function matchRequest(Request $request): array
    {
        return $this->router->matchRequest($request);
    }

    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        if (isset($parameters['_back'])) {
            if (is_bool($parameters['_back'])) {
                $currentRequest = $this->requestStack->getCurrentRequest();
                if ($currentRequest !== null) {
                    if ($parameters['_back'] === true) {
                        $parameters['_back'] = $currentRequest->getRequestUri();
                    } else {
                        $parameters['_back'] = $currentRequest->query->get("_back");
                        if (!is_string($parameters['_back'])) {
                            unset($parameters['_back']);
                        }
                    }
                } else {
                    unset($parameters['_back']);
                }
            }
        } else {
            unset($parameters['_back']);
        }
        return $this->router->generate($name, $parameters, $referenceType);
    }

    public function match(string $pathinfo): array
    {
        return $this->router->match($pathinfo);
    }
}
