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
    $stmt = $pdo->prepare("
        UPDATE mci_wp_import_runs
        SET status='failed', finished_at=NOW(6), error_message='Marked failed after interrupted process'
        WHERE status='running'
    ");
    $stmt->execute();
    echo "updated=" . $stmt->rowCount() . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

