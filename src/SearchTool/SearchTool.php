<?php

namespace App\SearchTool;

use Doctrine\ORM\QueryBuilder;

class SearchTool
{
    private array $handlers = [];
    private array $defaultSearch = [];

    public function __construct(private string $varPrefix = "var")
    {
    }

    public function handle(string $type, callable $handler, bool $inDefaultSearch = true): self
    {
        $this->handlers[$type] = $handler;
        if ($inDefaultSearch) {
            $this->defaultSearch[$type] = true;
        } else {
            unset($this->defaultSearch[$type]);
        }
        return $this;
    }

    public function search(QueryBuilder $queryBuilder, string $query): self
    {
        $query = QueryParser::parse($query);
        $builder = new Builder($queryBuilder, $this->varPrefix);
        foreach ($query as list($type, $string)) {
            $expression = $this->buildExpression($type, $string, $builder);
            $queryBuilder->andWhere($expression);
        }
        foreach ($builder->getJoins() as $alias => list($method, $field)) {
            $queryBuilder->$method($field, $alias);
        }
        foreach ($builder->getVars() as $var => $value) {
            $queryBuilder->setParameter($var, $value);
        }
        return $this;
    }

    private function buildExpression(?string $type, string $string, Builder $builder): mixed
    {
        $handlers = $this->getHandlersFor($type);
        $expressions = [];
        foreach ($handlers as $handler) {
            $builder->setup($string, $type);
            $expression = $handler($builder);
            if ($expression !== null) {
                $expressions[] = $expression;
            }
        }

        if (empty($expressions)) {
            return $builder->expr()->eq(0, 1);
        }

        if (count($expressions) === 1) {
            return $expressions[0];
        }

        return $builder->expr()->orX(...$expressions);
    }

    private function getHandlersFor(?string $type): array
    {
        if ($type === null) {
            return array_map(fn($key) => $this->handlers[$key], array_keys($this->defaultSearch));
        } else {
            return isset($this->handlers[$type]) ? [$this->handlers[$type]] : [];
        }
    }
}
