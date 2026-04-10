<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

require_once dirname(__DIR__, 2) . '/includes/mci_load_env.php';
mci_load_dotenv();

/**
 * Usage:
 *   php tools/wp_import/restore_sql_dump.php "C:\path\dump.sql" temp_db_name [--no-create] [--from-prefix=mci_] [--to-prefix=wpimp_]
 */

function usage(): void
{
    echo "Usage: php tools/wp_import/restore_sql_dump.php <dump.sql> <target_db_name> [--no-create] [--from-prefix=mci_] [--to-prefix=wpimp_]\n";
}

function env_required(string $key): string
{
    $v = getenv($key);
    if (!is_string($v) || trim($v) === '') {
        throw new RuntimeException("Missing required env var: {$key}");
    }
    return trim($v);
}

function root_pdo(): PDO
{
    $host = env_required('MCI_DB_HOST');
    $user = env_required('MCI_DB_USER');
    $pass = env_required('MCI_DB_PASS');
    $dsn = "mysql:host={$host};charset=utf8mb4";
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function db_pdo(string $dbName): PDO
{
    $host = env_required('MCI_DB_HOST');
    $user = env_required('MCI_DB_USER');
    $pass = env_required('MCI_DB_PASS');
    $dsn = "mysql:host={$host};dbname={$dbName};charset=utf8mb4";
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function should_skip_statement(string $stmt): bool
{
    $s = ltrim($stmt);
    $u = strtoupper($s);
    if ($s === '') {
        return true;
    }
    if (str_starts_with($u, 'START TRANSACTION')) {
        return true;
    }
    if (str_starts_with($u, 'COMMIT')) {
        return true;
    }
    if (str_starts_with($u, 'USE ')) {
        return true;
    }
    if (str_starts_with($u, 'CREATE DATABASE')) {
        return true;
    }
    return false;
}

function import_sql(PDO $pdo, string $sqlPath, ?string $fromPrefix = null, ?string $toPrefix = null): array
{
    $fh = fopen($sqlPath, 'rb');
    if ($fh === false) {
        throw new RuntimeException("Cannot open SQL file: {$sqlPath}");
    }

    $statement = '';
    $total = 0;
    $ok = 0;
    $errors = 0;
    $inSingle = false;
    $inDouble = false;

    while (($line = fgets($fh)) !== false) {
        $trim = ltrim($line);
        if (!$inSingle && !$inDouble) {
            if ($trim === '' || str_starts_with($trim, '-- ') || str_starts_with($trim, '/*') || str_starts_with($trim, '*/')) {
                continue;
            }
            if (stripos($trim, 'DELIMITER ') === 0) {
                continue;
            }
        }

        $len = strlen($line);
        for ($i = 0; $i < $len; $i++) {
            $ch = $line[$i];
            $prev = $i > 0 ? $line[$i - 1] : '';
            if ($ch === "'" && !$inDouble && $prev !== '\\') {
                $inSingle = !$inSingle;
            } elseif ($ch === '"' && !$inSingle && $prev !== '\\') {
                $inDouble = !$inDouble;
            }
        }

        $statement .= $line;
        if ($inSingle || $inDouble) {
            continue;
        }

        if (preg_match('/;\s*$/', rtrim($line)) === 1) {
            $total++;
            $stmt = trim($statement);
            $statement = '';
            if (should_skip_statement($stmt)) {
                continue;
            }
            if ($fromPrefix !== null && $toPrefix !== null && $fromPrefix !== '' && $toPrefix !== '') {
                $stmt = str_replace('`' . $fromPrefix, '`' . $toPrefix, $stmt);
                $stmt = str_replace(' ' . $fromPrefix, ' ' . $toPrefix, $stmt);
            }
            try {
                $pdo->exec($stmt);
                $ok++;
            } catch (Throwable $e) {
                $errors++;
                fwrite(STDERR, "SQL error on statement #{$total}: " . $e->getMessage() . PHP_EOL);
            }
        }
    }

    fclose($fh);
    return ['total' => $total, 'ok' => $ok, 'errors' => $errors];
}

try {
    $argv = $_SERVER['argv'] ?? [];
    if (count($argv) < 3) {
        usage();
        exit(1);
    }
    $sqlPath = (string)$argv[1];
    $dbName = trim((string)$argv[2]);
    $noCreate = in_array('--no-create', $argv, true);
    $fromPrefix = null;
    $toPrefix = null;
    foreach ($argv as $arg) {
        if (str_starts_with((string)$arg, '--from-prefix=')) {
            $fromPrefix = substr((string)$arg, strlen('--from-prefix='));
        }
        if (str_starts_with((string)$arg, '--to-prefix=')) {
            $toPrefix = substr((string)$arg, strlen('--to-prefix='));
        }
    }
    if ($dbName === '') {
        throw new RuntimeException('Target DB name cannot be empty.');
    }
    if (!is_file($sqlPath)) {
        throw new RuntimeException("SQL file not found: {$sqlPath}");
    }

    $root = root_pdo();
    if (!$noCreate) {
        $root->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }
    $root->exec("SET FOREIGN_KEY_CHECKS=0");

    $db = db_pdo($dbName);
    $db->exec("SET FOREIGN_KEY_CHECKS=0");

    $result = import_sql($db, $sqlPath, $fromPrefix, $toPrefix);
    $db->exec("SET FOREIGN_KEY_CHECKS=1");
    $root->exec("SET FOREIGN_KEY_CHECKS=1");

    echo "Restore complete\n";
    echo "Database: {$dbName}\n";
    echo "Statements: {$result['total']}\n";
    echo "Executed: {$result['ok']}\n";
    echo "Errors: {$result['errors']}\n";
    if ($fromPrefix !== null || $toPrefix !== null) {
        echo "Prefix rewrite: " . ($fromPrefix ?? '(none)') . " => " . ($toPrefix ?? '(none)') . "\n";
    }
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Restore failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

