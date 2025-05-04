<?php

namespace App\Enum;

use Exception;
use ReflectionClassConstant;

class Enumerator
{
    private static array $enumCache = [];

    private static function createParams(string $class, mixed $value): array
    {
        $params = [];
        $ref = new ReflectionClassConstant($class, $value->name);
        foreach ($ref->getAttributes() as $attribute) {
            if (!in_array($attribute->getName(), [Description::class, Parameter::class])) {
                continue;
            }
            $attribute = $attribute->newInstance();
            assert(($attribute instanceof Description) || ($attribute instanceof Parameter));
            $key = $attribute->getKey();
            if (array_key_exists($key, $params)) {
                throw new Exception(sprintf(
                    "Multiple declarations of %s in enum %s value %s",
                    $attribute->getParamName(),
                    $class,
                    $value->name
                ));
            }
            $params[$key] = $attribute->getValue();
        }
        if (!isset($params['d'])) {
            if (is_string($value->value)) {
                $description = $value->value;
            } else {
                $description = $value->name;
            }
            $params['d'] = $description;
        }

        return $params;
    }

    private static function getRealParam(mixed $value, string $param): mixed
    {
        $class = is_object($value) ? get_class($value) : null;
        if ($class === null || !enum_exists($class)) {
            throw new Exception("Enumerator can find parameters only for enum values");
        }
        
        $hash = spl_object_hash($value);

        if (!isset(self::$enumCache[$hash])) {
            self::$enumCache[$hash] = self::createParams($class, $value);
        }

        if (array_key_exists($param, self::$enumCache[$hash])) {
            return self::$enumCache[$hash][$param];
        }

        $param = preg_replace('/^p\./', '', $param);

        throw new MissingParamException(
            sprintf("Missing parameter %s in enum %s value %s", $param, $class, $value->name)
        );
    }

    public static function hasParam(mixed $value, string $param): bool
    {
        try {
            self::getParam($value, $param);
        } catch (MissingParamException $e) {
            return false;
        }
        return true;
    }

    public static function getParam(mixed $value, string $param): mixed
    {
        return self::getRealParam($value, "p." . $param);
    }
    public static function getDescription(mixed $value): string
    {
        return self::getRealParam($value, "d");
    }

    public static function isEnum(mixed $value): bool
    {
        return is_object($value) && enum_exists(get_class($value));
    }
}
