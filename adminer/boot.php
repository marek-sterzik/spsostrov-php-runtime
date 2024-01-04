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

    public function loginForm()
    {
        $drivers = [
            "mysql" => "server",
            "mysqli" => "server",
            "pdo_mysql" => "server",
            "sqlite3" => "sqlite",
            "pdo_sqlite" => "sqlite",
            "sqlite2" => "sqlite2",
            "pgsql" => "pgsq",
            "pdo_pgsql" => "pgsq",
            "oci8" => "oracle",
            "pdo_oci" => "oracle",
            "mssql" => "mssql",
            "pdo_sqlsrv" => "mssql",
            "mongo" => "mongo",
            "elastic" => "elastic",
        ];

        if (!isset($drivers[self::$dbConf['scheme']])) {
            return null;
        }

        $data = [
            "driver" => $drivers[self::$dbConf['scheme']],
            "server" => "",
            "username" => "",
            "password" => "",
            "db" => ""
        ];
        foreach ($data as $var => $value) {
            echo sprintf("<input type=\"hidden\" name=\"auth[%s]\" value=\"%s\">\n", htmlspecialchars($var), htmlspecialchars($value));
        }
        $nonce = get_nonce();
        echo "<input type=\"submit\" value=\"Login\">\n";
        echo "<script type=\"text/javascript\" nonce=\"$nonce\">\n";
        echo "window.onload = () => {qs('form').submit()}\n";
        echo "</script>\n";
        return true;
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

if (isset($_GET['username']) && is_string($_GET['username']) && !isset($_GET['db'])) {
    $database = (new AdminerLoginPasswordLess())->database();
    $queryString = 'username='.urlencode($_GET['username']).'&db='.urlencode($database);
    $uri = preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']) . "?" . $queryString;
    header("Location: " . $uri);
} else {
    include __DIR__.'/adminer.php';
}
