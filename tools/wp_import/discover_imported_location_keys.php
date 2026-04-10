<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

require_once dirname(__DIR__, 2) . '/includes/mci_load_env.php';
mci_load_dotenv();

function env_req(string $key): string
{
    $v = getenv($key);
    if (!is_string($v) || trim($v) === '') {
        throw new RuntimeException("Missing {$key}");
    }
    return trim($v);
}

function env_opt(string $key, ?string $default = null): ?string
{
    $v = getenv($key);
    if (!is_string($v) || trim($v) === '') {
        return $default;
    }
    return trim($v);
}

function pdo_mysql(string $host, string $db, string $user, string $pass, ?string $port = null): PDO
{
    $portPart = ($port !== null && $port !== '') ? ';port=' . (int)$port : '';
    return new PDO(
        "mysql:host={$host}{$portPart};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

function detect_wp_prefix(PDO $wp): string
{
    $configured = env_opt('WP_TABLE_PREFIX');
    if ($configured !== null) return $configured;
    $rows = $wp->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);
    foreach ($rows as $r) {
        $table = (string)($r[0] ?? '');
        if (str_ends_with($table, 'posts')) return substr($table, 0, -5);
    }
    throw new RuntimeException('Could not detect WP table prefix');
}

try {
    $mci = pdo_mysql(env_req('MCI_DB_HOST'), env_req('MCI_DB_NAME'), env_req('MCI_DB_USER'), env_req('MCI_DB_PASS'), env_opt('MCI_DB_PORT'));
    $wp = pdo_mysql(env_req('WP_DB_HOST'), env_req('WP_DB_NAME'), env_req('WP_DB_USER'), env_req('WP_DB_PASS'), env_opt('WP_DB_PORT'));
    $prefix = detect_wp_prefix($wp);

    $ids = $mci->query("SELECT source_id FROM mci_wp_import_map WHERE source_type='wp_post' AND target_type='mci_business_group'")->fetchAll(PDO::FETCH_COLUMN);
    if (!$ids) {
        echo "No mapped wp_post ids.\n";
        exit(0);
    }
    $ids = array_map('intval', $ids);

    $allCounts = [];
    foreach (array_chunk($ids, 400) as $chunk) {
        $in = implode(',', array_fill(0, count($chunk), '?'));
        $stmt = $wp->prepare("SELECT meta_key, COUNT(*) c FROM {$prefix}postmeta WHERE post_id IN ({$in}) GROUP BY meta_key");
        $stmt->execute($chunk);
        foreach ($stmt->fetchAll() as $row) {
            $k = (string)$row['meta_key'];
            $allCounts[$k] = ($allCounts[$k] ?? 0) + (int)$row['c'];
        }
    }
    arsort($allCounts);
    echo "Top meta keys across mapped imported posts:\n";
    $i = 0;
    foreach ($allCounts as $k => $c) {
        echo "- {$k}: {$c}\n";
        $i++;
        if ($i >= 60) break;
    }

    echo "\nLikely location/contact keys present:\n";
    foreach ($allCounts as $k => $c) {
        $kl = strtolower($k);
        if (
            str_contains($kl, 'address') || str_contains($kl, 'city') || str_contains($kl, 'state') ||
            str_contains($kl, 'country') || str_contains($kl, 'zip') || str_contains($kl, 'pin') ||
            str_contains($kl, 'phone') || str_contains($kl, 'mobile') || str_contains($kl, 'lat') ||
            str_contains($kl, 'lng') || str_contains($kl, 'long') || str_contains($kl, 'website') || str_contains($kl, 'url')
        ) {
            echo "- {$k}: {$c}\n";
        }
    }
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

