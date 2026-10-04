<?php

function goodlife_db()
{
    static $connection = null;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $configPath = __DIR__ . '/config.local.php';
    if (!is_file($configPath)) {
        throw new RuntimeException('Database config missing. Copy database/config.example.php to database/config.local.php and set its password.');
    }

    $config = require $configPath;
    if (!is_array($config)) {
        throw new RuntimeException('Database config must return an array.');
    }

    $readSetting = static function ($key, $environmentVariable) use ($config) {
        $environmentValue = getenv($environmentVariable);
        if (is_string($environmentValue) && $environmentValue !== '') {
            return $environmentValue;
        }
        return $config[$key] ?? null;
    };

    $host = $readSetting('host', 'DB_HOST');
    $port = $readSetting('port', 'DB_PORT');
    $database = $readSetting('database', 'DB_DATABASE');
    $username = $readSetting('username', 'DB_USERNAME');
    $password = $readSetting('password', 'DB_PASSWORD');

    if (!is_string($host) || $host === '' ||
        !is_numeric($port) || (int)$port < 1 || (int)$port > 65535 ||
        !is_string($database) || $database === '' ||
        !is_string($username) || $username === '' ||
        !is_string($password) || $password === '' ||
        $password === 'REPLACE_WITH_YOUR_LOCAL_DATABASE_PASSWORD') {
        throw new RuntimeException('Database config has missing or placeholder connection settings.');
    }

    $dsn = 'mysql:host=' . $host . ';port=' . (int)$port .
        ';dbname=' . $database . ';charset=utf8mb4';
    $connection = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5,
    ]);

    return $connection;
}
