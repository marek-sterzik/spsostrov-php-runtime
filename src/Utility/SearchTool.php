<?php

namespace App\Utility;

use Doctrine\ORM\QueryBuilder;

class SearchTool
{
    private array $perTypeHandlers = [];
    private mixed $defaultHandler = null;

    public function handle(?string $type, callable $handler): self
    {
        if ($type === null) {
            $this->defaultHandler = $handler;
        } else {
            $this->perTypeHandlers[$type] = $handler;
        }
        return $this;
    }

    private function getHandler(?string $type): ?callable
    {
        if ($type === null) {
            return $this->defaultHandler;
        } else {
            return $this->perTypeHandlers[$type] ?? null;
        }
    }

    private function getErrorHandler(): callable
    {
        return function (QueryBuilder $queryBuilder) {
            $queryBuilder->andWhere('0 = 1');
        };
    }

    public function search(QueryBuilder $queryBuilder, string $query): self
    {
        $query = QueryParser::parse($query);
        foreach ($query as $i => list($type, $string)) {
            $handler = $this->getHandler($type) ?? $this->getErrorHandler();
            $handler($queryBuilder, $string, $type, "var" . ($i + 1));
        }
        return $this;
    }
}
