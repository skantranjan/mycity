<?php
/**
 * CLI migration runner.
 * Usage: php migrate.php
 *
 * Runs all SQL files in api/v1/migrations/ in numeric order.
 * Tracks applied migrations in a `mci_migrations` table.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once __DIR__ . '/api/v1/lib/db.php';

$pdo = api_db();

// Ensure tracking table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `mci_migrations` (
      `filename` varchar(255) NOT NULL,
      `applied_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`filename`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Get already-applied migrations
$applied = [];
foreach ($pdo->query("SELECT filename FROM mci_migrations")->fetchAll(PDO::FETCH_COLUMN) as $f) {
    $applied[$f] = true;
}

// Find migration files
$dir   = __DIR__ . '/api/v1/migrations';
$files = glob($dir . '/[0-9]*.sql');
sort($files);

$ran = 0;
foreach ($files as $path) {
    $filename = basename($path);
    if (isset($applied[$filename])) {
        echo "  SKIP  $filename\n";
        continue;
    }
    $sql = file_get_contents($path);
    if ($sql === false) {
        echo "  ERR   $filename (cannot read file)\n";
        continue;
    }
    try {
        // Split on semicolons to run multiple statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            static fn(string $s) => $s !== ''
        );
        foreach ($statements as $stmt) {
            $pdo->exec($stmt);
        }
        $pdo->prepare("INSERT INTO mci_migrations (filename) VALUES (?)")->execute([$filename]);
        echo "  OK    $filename\n";
        $ran++;
    } catch (Throwable $e) {
        echo "  ERR   $filename: " . $e->getMessage() . "\n";
    }
}

if ($ran === 0) {
    echo "No new migrations to run.\n";
} else {
    echo "$ran migration(s) applied.\n";
}
