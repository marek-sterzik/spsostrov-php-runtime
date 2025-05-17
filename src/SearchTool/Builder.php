<?php

namespace App\SearchTool;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;

class Builder
{
    private int $varNumber = 1;
    private string $searchString;
    private ?string $searchType;
    private array $vars = [];

    public function __construct(private QueryBuilder $queryBuilder, private string $varPrefix = "var")
    {
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
