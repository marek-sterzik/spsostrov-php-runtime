<?php

namespace App\SearchTool;

use Exception;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;

class Builder
{
    private int $varNumber = 1;
    private string $searchString;
    private ?string $searchType;
    private array $vars = [];
    private array $joins = [];

    public function __construct(private QueryBuilder $queryBuilder, private string $varPrefix = "var")
    {
    }

    private function addJoin(string $method, string $field, string $alias): self
    {
        if (isset($this->joins[$alias])) {
            if ($this->joins[$alias][0] !== $method || $this->joins[$alias][1] !== $field) {
                throw new Exception(sprintf(
                    "Cannot register conditional join: Alias '%s' has already registered field %s, " .
                    "cannot register another field %s",
                    $alias,
                    $this->joins[$alias],
                    $field
                ));
            }
        } else {
            $this->joins[$alias] = [$method, $field];
        }
        return $this;
    }

    public function join(string $field, string $alias): self
    {
        return $this->addJoin("join", $field, $alias);
    }

    public function innerJoin(string $field, string $alias): self
    {
        return $this->addJoin("innerJoin", $field, $alias);
    }

    public function leftJoin(string $field, string $alias): self
    {
        return $this->addJoin("leftJoin", $field, $alias);
    }

    public function setup(string $searchString, ?string $searchType): self
    {
        $this->searchString = $searchString;
        $this->searchType = $searchType;
        return $this;
    }

    public function getVars(): array
    {
        return $this->vars;
    }

    public function getJoins(): array
    {
        return $this->joins;
    }

    public function searchString(): string
    {
        return $this->searchString;
    }

    public function searchType(): ?string
    {
        return $this->searchType;
    }

    public function var(mixed $value): string
    {
        $var = ":" . $this->varPrefix . ($this->varNumber++);
        $this->vars[$var] = $value;
        return $var;
    }

    public function expr(): Expr
    {
        return $this->queryBuilder->expr();
    }
}
