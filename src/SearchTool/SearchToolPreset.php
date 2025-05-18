<?php

namespace App\SearchTool;

class SearchToolPreset
{
    public static function assignment(): SearchTool
    {
        $searchTool = new SearchTool();
        
        $searchTool->handle("caption", function (Builder $builder) {
            return $builder->expr()->like("a.caption", $builder->var("%" . $builder->searchString() . "%"));
        });

        $searchTool->handle("description", function (Builder $builder) {
            return $builder->expr()->like("a.description", $builder->var("%" . $builder->searchString() . "%"));
        });

        $searchTool->handle("owner", function (Builder $builder) {
            $builder->leftJoin("a.owner", "u");
            $var = $builder->var("%" . $builder->searchString() . "%");
            return $builder->expr()->orX(
                $builder->expr()->like("u.username", $var),
                $builder->expr()->like("u.name", $var),
            );
        }, false);

        self::id("assignment-id", "a.id", $searchTool);
        self::id("owner-id", "u.id", $searchTool, ["leftJoin", "a.owner", "u"]);

        return $searchTool;
    }

    public static function submission(): SearchTool
    {
        $searchTool = new SearchTool();

        $searchTool->handle("caption", function (Builder $builder) {
            $builder->innerJoin('s.assignment', 'a');
            return $builder->expr()->like("a.caption", $builder->var("%" . $builder->searchString() . "%"));
        });

        $searchTool->handle("description", function (Builder $builder) {
            $builder->innerJoin('s.assignment', 'a');
            return $builder->expr()->like("a.description", $builder->var("%" . $builder->searchString() . "%"));
        });

        $searchTool->handle("submitter", function (Builder $builder) {
            $builder->innerJoin('s.submitter', 'u');
            $var = $builder->var("%" . $builder->searchString() . "%");
            return $builder->expr()->orX(
                $builder->expr()->like("u.username", $var),
                $builder->expr()->like("u.name", $var),
            );
        }, false);

        self::id("submission-id", "s.id", $searchTool);
        self::id("submitter-id", "u.id", $searchTool, ["innerJoin", "s.submitter", "u"]);
        self::id("assignment-id", "a.id", $searchTool, ["innerJoin", "s.assignment", "a"]);

        return $searchTool;
    }

    public static function user(): SearchTool
    {
        $searchTool = new SearchTool();
        $searchTool->handle("username", function (Builder $builder) {
            $var = $builder->var("%" . $builder->searchString() . "%");
            return $builder->expr()->like("u.username", $var);
        });
        $searchTool->handle("name", function (Builder $builder) {
            $var = $builder->var("%" . $builder->searchString() . "%");
            return $builder->expr()->like("u.name", $var);
        });
        $searchTool->handle("class", function (Builder $builder) {
            $var = $builder->var($builder->searchString() . "%");
            return $builder->expr()->orX(
                $builder->expr()->like("u.effectiveStudentClass", $var),
                $builder->expr()->andX(
                    $builder->expr()->isNull("u.effectiveStudentClass"),
                    $builder->expr()->like("u.originalStudentClass", $var)
                )
            );
        });

        self::id("user-id", "u.id", $searchTool);

        return $searchTool;
    }

    private static function id(string $searchField, string $idField, SearchTool $searchTool, ?array $join = null): void
    {
        $searchTool->handle($searchField, function (Builder $builder) use ($idField, $join) {
            if ($join !== null) {
                list($method, $field, $alias) = $join;
                $builder->$method($field, $alias);
            }
            $id = $builder->searchString();
            if (preg_match('/^[0-9]+$/', $id)) {
                return $builder->expr()->eq($idField, $builder->var((int)$id));
            }
            return null;
        }, false);
    }
}
