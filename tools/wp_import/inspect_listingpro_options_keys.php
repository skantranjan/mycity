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
    if (!is_string($v) || trim($v) === '') throw new RuntimeException("Missing {$key}");
    return trim($v);
}
function env_opt(string $key, ?string $default = null): ?string
{
    $v = getenv($key);
    return (is_string($v) && trim($v) !== '') ? trim($v) : $default;
}
function pdo_mysql(string $host, string $db, string $user, string $pass, ?string $port = null): PDO
{
    $portPart = ($port !== null && $port !== '') ? ';port=' . (int)$port : '';
    return new PDO("mysql:host={$host}{$portPart};dbname={$db};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
function detect_wp_prefix(PDO $wp): string
{
    $configured = env_opt('WP_TABLE_PREFIX');
    if ($configured !== null) return $configured;
    $rows = $wp->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);
    foreach ($rows as $r) {
        $t = (string)($r[0] ?? '');
        if (str_ends_with($t, 'posts')) return substr($t, 0, -5);
    }
    throw new RuntimeException('WP prefix not found');
}

try {
    $mci = pdo_mysql(env_req('MCI_DB_HOST'), env_req('MCI_DB_NAME'), env_req('MCI_DB_USER'), env_req('MCI_DB_PASS'), env_opt('MCI_DB_PORT'));
    $wp  = pdo_mysql(env_req('WP_DB_HOST'), env_req('WP_DB_NAME'), env_req('WP_DB_USER'), env_req('WP_DB_PASS'), env_opt('WP_DB_PORT'));
    $pfx = detect_wp_prefix($wp);

    $ids = $mci->query("SELECT source_id FROM mci_wp_import_map WHERE source_type='wp_post' AND target_type='mci_business_group' LIMIT 15")->fetchAll(PDO::FETCH_COLUMN);
    if (!$ids) {
        echo "No mapped posts.\n";
        exit(0);
    }
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $wp->prepare("SELECT post_id, meta_value FROM {$pfx}postmeta WHERE meta_key='lp_listingpro_options' AND post_id IN ({$in})");
    $stmt->execute(array_map('intval', $ids));
    $rows = $stmt->fetchAll();

    $seenKeys = [];
    foreach ($rows as $row) {
        $v = (string)$row['meta_value'];
        $parsed = @unserialize($v);
        if (!is_array($parsed)) continue;
        foreach (array_keys($parsed) as $k) {
            $seenKeys[(string)$k] = true;
        }
    }

    $keys = array_keys($seenKeys);
    sort($keys);
    echo "lp_listingpro_options keys (sample):\n";
    foreach ($keys as $k) {
        echo "- {$k}\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

