<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/mci_load_env.php';
mci_load_dotenv();

$host = getenv('MCI_DB_HOST') ?: '';
$user = getenv('MCI_DB_USER') ?: '';
$pass = getenv('MCI_DB_PASS') ?: '';
$db   = $argv[1] ?? '';

if ($db === '') {
    fwrite(STDERR, "Usage: php tools/wp_import/test_db_conn.php <db_name>\n");
    exit(1);
}

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $count = (int)$pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchColumn();
    echo "ok tables={$count}\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

