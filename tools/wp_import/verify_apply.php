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

    $run = $pdo->query("
        SELECT id, status, is_dry_run, started_at, finished_at
        FROM mci_wp_import_runs
        ORDER BY started_at DESC
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);

    if (!$run) {
        echo json_encode(['error' => 'no_run'], JSON_UNESCAPED_SLASHES) . PHP_EOL;
        exit(1);
    }

    $runId = (string)$run['id'];
    $stmtErr = $pdo->prepare("SELECT COUNT(*) FROM mci_wp_import_logs WHERE run_id = ? AND level = 'error'");
    $stmtErr->execute([$runId]);
    $errorCount = (int)$stmtErr->fetchColumn();

    $summaryStmt = $pdo->prepare("SELECT summary_json FROM mci_wp_import_runs WHERE id = ?");
    $summaryStmt->execute([$runId]);
    $summaryJson = (string)$summaryStmt->fetchColumn();
    $summary = json_decode($summaryJson, true);

    $counts = [];
    foreach ([
        'mci_users',
        'mci_userprofiles',
        'mci_categories',
        'mci_business_groups',
        'mci_business_branches',
        'mci_business_images',
        'mci_wp_import_map',
    ] as $table) {
        $counts[$table] = (int)$pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    }

    echo json_encode([
        'run' => $run,
        'summary' => $summary,
        'error_logs' => $errorCount,
        'counts' => $counts,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

