<?php

class AdminerLoginPasswordLess
{
    private static $dbConf = null;

    public static function setDbConf($dbConf)
    {
        if (is_string($dbConf)) {
            $dbConf = parse_url($dbConf);
        }
        if (is_array($dbConf)) {
            static::$dbConf = $dbConf;
        }
    }

    public static function isConfigured(): bool
    {
        return static::$dbConf !== null;
    }

    public function login($username, $password)
    {
        return true;
    }

	public function credentials()
    {
        $host = static::$dbConf['host'];
        if (isset(static::$dbConf['port'])) {
            $host .= ":" . static::$dbConf['port'];
        }
        return [$host, static::$dbConf['user'], static::$dbConf['pass']];
	}

    public function database()
    {
        return ltrim(static::$dbConf['path'], '/');
    }
}


AdminerLoginPasswordLess::setDbConf($dbConf ?? null);

function adminer_object()
{
    include_once __DIR__ . "/adminer-plugin.php";
    $plugins = [];
    if (AdminerLoginPasswordLess::isConfigured()) {
        $plugins[] = new AdminerLoginPasswordLess();
    }

    return new AdminerPlugin($plugins);
}


if (defined("SID") && session_status() !== PHP_SESSION_ACTIVE){
    session_start();
}

include __DIR__.'/adminer.php';
