<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/connection.php';

try {
    $connection = goodlife_db();
    $result = $connection->query('SELECT DATABASE() AS database_name, VERSION() AS server_version, CURRENT_USER() AS database_user');
    $details = $result->fetch();
    $tableCount = (int)$connection->query(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'
    )->fetchColumn();

    echo 'Connected to database: ' . $details['database_name'] . PHP_EOL;
    echo 'Database account: ' . $details['database_user'] . PHP_EOL;
    echo 'MySQL/MariaDB version: ' . $details['server_version'] . PHP_EOL;
    echo 'Tables found: ' . $tableCount . PHP_EOL;
} catch (PDOException $error) {
    fwrite(STDERR, 'Database connection failed (SQLSTATE ' . $error->getCode() . '). Check the local config, database service, and account grants.' . PHP_EOL);
    exit(1);
} catch (RuntimeException $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
