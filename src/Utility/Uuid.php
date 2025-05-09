<?php

namespace App\Utility;

use Ramsey\Uuid\Provider\Node\RandomNodeProvider;
use Ramsey\Uuid\Provider\NodeProviderInterface;
use Ramsey\Uuid\Uuid as RamseyUuid;
use Ramsey\Uuid\UuidInterface as RamseyUuidInterface;

final class Uuid
{
    /** @var NodeProviderInterface */
    private static $nodeProvider = null;

    public static function uuid6(): RamseyUuidInterface
    {
        return RamseyUuid::uuid6(self::getNodeProvider()->getNode());
    }

    private static function getNodeProvider(): NodeProviderInterface
    {
        if (self::$nodeProvider === null) {
            self::$nodeProvider = new RandomNodeProvider();
        }
        return static::$nodeProvider;
    }
}
