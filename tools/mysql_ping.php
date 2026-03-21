<?php
/**
 * Quick DB connectivity check using project .env (run from repo root: php tools/mysql_ping.php)
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/includes/mci_load_env.php';
mci_load_dotenv($root . DIRECTORY_SEPARATOR . '.env');

$keys = ['MCI_DB_HOST', 'MCI_DB_NAME', 'MCI_DB_USER', 'MCI_DB_PASS'];
foreach ($keys as $k) {
    $v = getenv($k);
    if (!is_string($v) || trim($v) === '') {
        fwrite(STDERR, "Missing env: {$k}\n");
        exit(1);
    }
}

$host = getenv('MCI_DB_HOST');
$name = getenv('MCI_DB_NAME');
$user = getenv('MCI_DB_USER');
$pass = getenv('MCI_DB_PASS');

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$name};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $ver = $pdo->query('SELECT VERSION()')->fetchColumn();
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    echo "OK — connected to {$host} / {$db}\n";
    echo "Server version: {$ver}\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . "\n");
    exit(1);
}
