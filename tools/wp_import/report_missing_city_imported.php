<?php
declare(strict_types=1);

/**
 * Export remaining imported businesses with missing/unknown city + raw WP meta hints.
 * Output: docs/import/missing-city-imported-report.md
 */
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

require_once dirname(__DIR__, 2) . '/includes/mci_load_env.php';
mci_load_dotenv();

function env_req(string $k): string
{
    $v = getenv($k);
    if (!is_string($v) || trim($v) === '') {
        throw new RuntimeException("Missing {$k}");
    }
    return trim($v);
}

function env_opt(string $k, ?string $d = null): ?string
{
    $v = getenv($k);
    return (is_string($v) && trim($v) !== '') ? trim($v) : $d;
}

function pdo_mysql(string $h, string $db, string $u, string $p, ?string $port = null): PDO
{
    $pp = ($port !== null && $port !== '') ? ';port=' . (int)$port : '';
    return new PDO(
        "mysql:host={$h}{$pp};dbname={$db};charset=utf8mb4",
        $u,
        $p,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

function detect_wp_prefix(PDO $wp): string
{
    $cfg = env_opt('WP_TABLE_PREFIX');
    if ($cfg !== null) {
        return $cfg;
    }
    foreach ($wp->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM) as $r) {
        $t = (string)($r[0] ?? '');
        if (str_ends_with($t, 'posts')) {
            return substr($t, 0, -5);
        }
    }
    throw new RuntimeException('WP prefix not found');
}

function esc_md(string $s): string
{
    return str_replace(["\r\n", "\r", "\n"], ' ', $s);
}

try {
    $mci = pdo_mysql(
        env_req('MCI_DB_HOST'),
        env_req('MCI_DB_NAME'),
        env_req('MCI_DB_USER'),
        env_req('MCI_DB_PASS'),
        env_opt('MCI_DB_PORT')
    );
    $wp = pdo_mysql(
        env_req('WP_DB_HOST'),
        env_req('WP_DB_NAME'),
        env_req('WP_DB_USER'),
        env_req('WP_DB_PASS'),
        env_opt('WP_DB_PORT')
    );
    $pfx = detect_wp_prefix($wp);

    $rows = $mci->query("
        SELECT
          m.source_id AS wp_post_id,
          g.id AS group_id,
          g.name,
          g.slug,
          b.id AS branch_id,
          b.address_line1,
          b.city,
          b.state,
          b.country,
          b.pincode
        FROM mci_wp_import_map m
        INNER JOIN mci_business_groups g ON g.id = m.target_id AND g.status != 'deleted'
        INNER JOIN mci_business_branches b ON b.business_group_id = g.id AND b.is_primary = 1
        WHERE m.source_type = 'wp_post' AND m.target_type = 'mci_business_group'
          AND (b.city IS NULL OR TRIM(b.city) = '' OR LOWER(TRIM(b.city)) = 'unknown')
        ORDER BY g.name
    ")->fetchAll();

    $postIds = array_map(static fn(array $r): int => (int)$r['wp_post_id'], $rows);
    $metaByPostId = [];
    if ($postIds !== []) {
        foreach (array_chunk($postIds, 200) as $chunk) {
            $in = implode(',', array_fill(0, count($chunk), '?'));
            $stmt = $wp->prepare("
                SELECT post_id, meta_key, meta_value
                FROM {$pfx}postmeta
                WHERE post_id IN ({$in})
                  AND meta_key IN (
                    'lp_listingpro_options',
                    'city','_city','business_city','town',
                    'address','_address','business_address','street_address',
                    'mappin'
                  )
            ");
            $stmt->execute($chunk);
            foreach ($stmt->fetchAll() as $m) {
                $metaByPostId[(int)$m['post_id']][(string)$m['meta_key']] = (string)$m['meta_value'];
            }
        }
    }

    $dir = dirname(__DIR__, 2) . '/docs/import';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $path = $dir . '/missing-city-imported-report.md';

    $lines = [
        '# Imported businesses — still missing city',
        '',
        '- Generated at: `' . gmdate('c') . '`',
        '- Count: `' . count($rows) . '`',
        '',
    ];

    foreach ($rows as $r) {
        $pid = (int)$r['wp_post_id'];
        $meta = $metaByPostId[$pid] ?? [];
        $lpRaw = (string)($meta['lp_listingpro_options'] ?? '');
        $lp = @unserialize($lpRaw);
        if (!is_array($lp)) {
            $lp = [];
        }

        $gAddress = (string)($lp['gAddress'] ?? '');
        $mappin = (string)($lp['mappin'] ?? '');
        $lat = (string)($lp['latitude'] ?? '');
        $lng = (string)($lp['longitude'] ?? '');
        $phone = (string)($lp['phone'] ?? '');
        $website = (string)($lp['website'] ?? '');

        $lines[] = '## ' . esc_md((string)$r['name']);
        $lines[] = '- `business_group_id`: `' . esc_md((string)$r['group_id']) . '`';
        $lines[] = '- `slug`: `' . esc_md((string)$r['slug']) . '`';
        $lines[] = '- `wp_post_id`: `' . $pid . '`';
        $lines[] = '- `branch_id`: `' . esc_md((string)$r['branch_id']) . '`';
        $lines[] = '- Current DB `city`: `' . esc_md(trim((string)$r['city'])) . '`';
        $lines[] = '- DB `address_line1`: `' . esc_md(mb_substr((string)$r['address_line1'], 0, 200)) . '`';
        $lines[] = '- DB `state` / `country` / `pincode`: `' . esc_md(trim((string)$r['state'])) . '` / `' . esc_md(trim((string)$r['country'])) . '` / `' . esc_md(trim((string)$r['pincode'])) . '`';
        $lines[] = '- WP meta `city` / `_city` / `business_city`: `' . esc_md(trim((string)($meta['city'] ?? ''))) . '` / `' . esc_md(trim((string)($meta['_city'] ?? ''))) . '` / `' . esc_md(trim((string)($meta['business_city'] ?? ''))) . '`';
        $lines[] = '- ListingPro `gAddress`: `' . esc_md(mb_substr($gAddress, 0, 400)) . '`';
        $lines[] = '- ListingPro `mappin`: `' . esc_md(mb_substr($mappin, 0, 200)) . '`';
        $lines[] = '- ListingPro `latitude` / `longitude`: `' . esc_md($lat) . '` / `' . esc_md($lng) . '`';
        $lines[] = '- ListingPro `phone` / `website`: `' . esc_md(mb_substr($phone, 0, 80)) . '` / `' . esc_md(mb_substr($website, 0, 120)) . '`';
        $lines[] = '';
    }

    file_put_contents($path, implode(PHP_EOL, $lines));
    echo "Wrote {$path}\n";
    echo 'Rows: ' . count($rows) . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Report failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
