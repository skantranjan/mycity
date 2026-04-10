<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/mci_load_env.php';
mci_load_dotenv();

$db = getenv('MCI_DB_NAME') ?: '';
$host = getenv('MCI_DB_HOST') ?: '';
$user = getenv('MCI_DB_USER') ?: '';
$pass = getenv('MCI_DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $tables = $pdo->query("SHOW TABLES LIKE 'wpimp_%'")->fetchAll(PDO::FETCH_NUM) ?: [];
    echo 'wpimp_tables=' . count($tables) . PHP_EOL;
    $checks = ['wpimp_posts', 'wpimp_postmeta', 'wpimp_terms', 'wpimp_term_taxonomy', 'wpimp_term_relationships', 'wpimp_users', 'wpimp_usermeta'];
    foreach ($checks as $t) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $stmt->execute([$t]);
        $exists = ((int)$stmt->fetchColumn()) > 0;
        if (!$exists) {
            echo $t . '=MISSING' . PHP_EOL;
            continue;
        }
        $cnt = (int)$pdo->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
        echo $t . '=' . $cnt . PHP_EOL;
    }
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

