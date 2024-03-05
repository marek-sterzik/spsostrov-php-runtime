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

function parse_users(string $usersPass): ?array
{
    $users = [];
    if ($usersPass !== '') {
        foreach (preg_split('/\s+/', $usersPass) as $userPass) {
            if ($userPass === '') {
                continue;
            }
            $parsed = explode(":", $userPass);
            if (count($parsed) !== 2) {
                return null;
            }
            list($username, $password) = $parsed;
            $username = urldecode($username);
            $password = urldecode($password);
            if (!isset($users[$username])) {
                $users[$username] = [];
            }
            $users[$username][] = $password;
        }
    }
    return $users;
}

function build_auth_callback($httpAuthorize): ?callable
{
    if ($httpAuthorize === null) {
        return null;
    }
    if (is_callable($httpAuthorize)) {
        return $httpAuthorize;
    }
    if (is_string($httpAuthorize)) {
        $users = parse_users($httpAuthorize);
        if ($users !== null) {
            return function ($user, $pass) use ($users) {
                foreach ($users[$user] ?? [] as $password) {
                    if ($password === $pass) {
                        return true;
                    }
                }
                return false;
            };
        }
    }

    return function($username, $password) {
        return false;
    };
}

function http_authorize($httpAuthorize)
{
    $httpAuthorize = build_auth_callback($httpAuthorize);
    if ($httpAuthorize !== null) {
        $username = $_SERVER['PHP_AUTH_USER'] ?? null;
        $password = $_SERVER['PHP_AUTH_PW'] ?? null;
        if (!is_string($username)) {
            $username = null;
        }
        if (!is_string($password)) {
            $password = null;
        }
        $authorized = $httpAuthorize($username, $password) ? true : false;
    } else {
        $authorized = true;
    }
    if (!$authorized) {
        header('WWW-Authenticate: Basic realm="Adminer"');
        header('HTTP/1.0 401 Unauthorized');
        echo "<!doctype html>\n<html><head><title>Unauthorized</title></head><body><h1>401 Unauthorized</h1></body></html>";
        exit;
    }
}

http_authorize($httpAuth ?? null);
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
