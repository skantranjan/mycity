<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/mci_load_env.php';
mci_load_dotenv();

$host = getenv('MCI_DB_HOST') ?: '';
$db = getenv('MCI_DB_NAME') ?: '';
$user = getenv('MCI_DB_USER') ?: '';
$pass = getenv('MCI_DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $row = $pdo->query("SELECT id, status, is_dry_run, started_at, finished_at FROM mci_wp_import_runs ORDER BY started_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo "no_runs\n";
        exit(0);
    }
    echo json_encode($row, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

