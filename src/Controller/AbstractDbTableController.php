<?php

namespace App\Controller;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\QueryBuilder;
use App\SearchTool\SearchTool;
use App\Exception\RequestCorrectionException;
use App\Utility\RequestUtility;

abstract class AbstractDbTableController extends AbstractTableController
{
    abstract protected function getHeader(array $filterData): array;

    protected function getBaseQueryBuilder(array $filterData): QueryBuilder
    {
        throw new Exception("getBaseQueryBuilder() is not implemented");
    }

    protected function buildSearchTool(): ?SearchTool
    {
        return null;
    }

    protected function getSearchField(): string
    {
        return 'q';
    }

    protected function applySearchTool(QueryBuilder $queryBuilder, array $filterData): QueryBuilder
    {
        $searchTool = $this->buildSearchTool();
        if ($searchTool !== null) {
            $searchTool->search($queryBuilder, $filterData[$this->getSearchField()] ?? '');
        }
        return $queryBuilder;
    }

    protected function getItemCountQuery(array $filterData): QueryBuilder
    {
        $queryBuilder = $this->getBaseQueryBuilder($filterData);
        $queryBuilder = $this->applySearchTool($queryBuilder, $filterData);
        return $queryBuilder->select("count(1)");
    }

    protected function setOrderByQuery(QueryBuilder $queryBuilder): QueryBuilder
    {
        return $queryBuilder;
    }

    protected function getDataQuery(int $page, int $pageSize, array $filterData): QueryBuilder
    {
        $queryBuilder = $this->getBaseQueryBuilder($filterData);
        $queryBuilder = $this->applySearchTool($queryBuilder, $filterData);
        $queryBuilder = $this->setOrderByQuery($queryBuilder);
        return $queryBuilder->setFirstResult($page * $pageSize)->setMaxResults($pageSize);
    }

    protected function getItemCount(array $filterData): int
    {
        $queryBuilder = $this->getItemCountQuery($filterData);
        return (int)($queryBuilder->getQuery()->getSingleScalarResult());
    }

    protected function getData(int $page, int $pageSize, array $filterData): array
    {
        $queryBuilder = $this->getDataQuery($page, $pageSize, $filterData);
        $data = [];
        foreach ($queryBuilder->getQuery()->getResult() as $record) {
            $data[] = $this->recordToArray($record);
        }
        return $data;
    }

    protected function recordToArray(mixed $record): array
    {
        if (is_array($record)) {
            return $record;
        } else {
            throw new Exception("recordToArray() object to array conversion not yet implemented");
        }
    }
}
