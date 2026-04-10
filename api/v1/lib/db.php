<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function api_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = api_db_config();
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $cfg['host'], $cfg['name']);
    if (!empty($cfg['port'])) {
        $dsn .= ';port=' . (int)$cfg['port'];
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5,
    ];
    if (!empty($cfg['persistent'])) {
        $options[PDO::ATTR_PERSISTENT] = true;
    }

    $lastError = null;
    for ($attempt = 1; $attempt <= 2; $attempt++) {
        try {
            $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], $options);
            break;
        } catch (PDOException $e) {
            $lastError = $e;
            $isConnectionRefused = str_contains($e->getMessage(), '[2002]');
            if (!$isConnectionRefused || $attempt === 2) {
                throw $e;
            }
            // Brief retry for transient DNS/network hiccups.
            usleep(200000);
        }
    }

    if (!$pdo instanceof PDO && $lastError instanceof PDOException) {
        throw $lastError;
    }

    return $pdo;
}

