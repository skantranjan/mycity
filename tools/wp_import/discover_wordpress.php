<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

require_once dirname(__DIR__, 2) . '/includes/mci_load_env.php';
mci_load_dotenv();

function env_or_fail(string $key): string
{
    $value = getenv($key);
    if (!is_string($value) || trim($value) === '') {
        throw new RuntimeException("Missing required env var: {$key}");
    }
    return trim($value);
}

function connect_wp(): PDO
{
    $host = env_or_fail('WP_DB_HOST');
    $name = env_or_fail('WP_DB_NAME');
    $user = env_or_fail('WP_DB_USER');
    $pass = env_or_fail('WP_DB_PASS');
    $port = getenv('WP_DB_PORT');
    $portPart = (is_string($port) && trim($port) !== '') ? ';port=' . (int)$port : '';

    return new PDO(
        "mysql:host={$host}{$portPart};dbname={$name};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

function detect_prefix(PDO $pdo): string
{
    $configured = getenv('WP_TABLE_PREFIX');
    if (is_string($configured) && trim($configured) !== '') {
        return trim($configured);
    }

    $rows = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM);
    foreach ($rows as $row) {
        $name = (string)($row[0] ?? '');
        if (str_ends_with($name, 'posts')) {
            return substr($name, 0, -5);
        }
    }
    throw new RuntimeException('Could not auto-detect WordPress table prefix. Set WP_TABLE_PREFIX in .env.');
}

function rows(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

try {
    $pdo = connect_wp();
    $pfx = detect_prefix($pdo);

    $report = [
        'generated_at' => gmdate('c'),
        'wp_prefix' => $pfx,
        'tables' => [],
        'post_types' => [],
        'taxonomies' => [],
        'meta_keys' => [],
        'users_total' => 0,
    ];

    $report['tables'] = array_map(
        static fn(array $r): string => (string)($r['table_name'] ?? ''),
        rows(
            $pdo,
            "SELECT table_name
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name LIKE ?
             ORDER BY table_name",
            [$pfx . '%']
        )
    );

    $postTypes = rows(
        $pdo,
        "SELECT post_type, COUNT(*) AS cnt
         FROM {$pfx}posts
         WHERE post_status NOT IN ('auto-draft','inherit')
         GROUP BY post_type
         ORDER BY cnt DESC"
    );
    foreach ($postTypes as $r) {
        $report['post_types'][] = [
            'post_type' => (string)$r['post_type'],
            'count' => (int)$r['cnt'],
        ];
    }

    $taxonomies = rows(
        $pdo,
        "SELECT taxonomy, COUNT(*) AS cnt
         FROM {$pfx}term_taxonomy
         GROUP BY taxonomy
         ORDER BY cnt DESC"
    );
    foreach ($taxonomies as $r) {
        $report['taxonomies'][] = [
            'taxonomy' => (string)$r['taxonomy'],
            'count' => (int)$r['cnt'],
        ];
    }

    $metaKeys = rows(
        $pdo,
        "SELECT meta_key, COUNT(*) AS cnt
         FROM {$pfx}postmeta
         WHERE meta_key IS NOT NULL
         GROUP BY meta_key
         ORDER BY cnt DESC
         LIMIT 300"
    );
    foreach ($metaKeys as $r) {
        $report['meta_keys'][] = [
            'meta_key' => (string)$r['meta_key'],
            'count' => (int)$r['cnt'],
        ];
    }

    $report['users_total'] = (int)$pdo->query("SELECT COUNT(*) FROM {$pfx}users")->fetchColumn();

    $outDir = dirname(__DIR__, 2) . '/docs/import';
    if (!is_dir($outDir)) {
        mkdir($outDir, 0777, true);
    }
    $outPath = $outDir . '/wordpress-discovery.json';
    file_put_contents($outPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    echo "WordPress discovery complete.\n";
    echo "Prefix: {$pfx}\n";
    echo "Output: {$outPath}\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Discovery failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

