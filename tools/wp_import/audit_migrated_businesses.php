<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

require_once dirname(__DIR__, 2) . '/includes/mci_load_env.php';
mci_load_dotenv();

function env_req(string $key): string
{
    $value = getenv($key);
    if (!is_string($value) || trim($value) === '') {
        throw new RuntimeException("Missing required env var: {$key}");
    }
    return trim($value);
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

function write_report(array $summary, array $samples): void
{
    $dir = dirname(__DIR__, 2) . '/docs/import';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $path = $dir . '/wordpress-import-data-audit-report.md';
    $lines = [
        '# WordPress Import Data Audit Report',
        '',
        '- Generated at: `' . gmdate('c') . '`',
        '',
        '## Summary',
        '- Imported businesses audited: `' . (string)$summary['imported_total'] . '`',
        '- Missing address: `' . (string)$summary['missing_address'] . '`',
        '- Missing city: `' . (string)$summary['missing_city'] . '`',
        '- Missing phone: `' . (string)$summary['missing_phone'] . '`',
        '- Missing website: `' . (string)$summary['missing_website'] . '`',
        '- Missing subcategories: `' . (string)$summary['missing_subcategory'] . '`',
        '',
    ];

    foreach ($samples as $section => $rows) {
        $lines[] = '## ' . $section;
        if (empty($rows)) {
            $lines[] = '- none';
            $lines[] = '';
            continue;
        }
        foreach ($rows as $row) {
            $lines[] = '- `business_id=' . (string)$row['id'] . '` name: ' . (string)$row['name'] . ' (`slug=' . (string)$row['slug'] . '`)';
        }
        $lines[] = '';
    }

    file_put_contents($path, implode(PHP_EOL, $lines));
}

try {
    $mci = pdo_mysql(
        env_req('MCI_DB_HOST'),
        env_req('MCI_DB_NAME'),
        env_req('MCI_DB_USER'),
        env_req('MCI_DB_PASS'),
        getenv('MCI_DB_PORT') ?: null
    );

    $importedTotal = (int)$mci->query("
        SELECT COUNT(*)
        FROM mci_business_groups g
        INNER JOIN mci_wp_import_map m
            ON m.target_type = 'mci_business_group'
           AND m.target_id = g.id
        WHERE g.status != 'deleted'
    ")->fetchColumn();

    $missingAddress = (int)$mci->query("
        SELECT COUNT(DISTINCT g.id)
        FROM mci_business_groups g
        INNER JOIN mci_wp_import_map m
            ON m.target_type = 'mci_business_group'
           AND m.target_id = g.id
        LEFT JOIN mci_business_branches b
            ON b.business_group_id = g.id
           AND b.is_primary = 1
        WHERE g.status != 'deleted'
          AND (b.address_line1 IS NULL OR TRIM(b.address_line1) = '' OR LOWER(TRIM(b.address_line1)) = 'address not provided')
    ")->fetchColumn();

    $missingCity = (int)$mci->query("
        SELECT COUNT(DISTINCT g.id)
        FROM mci_business_groups g
        INNER JOIN mci_wp_import_map m
            ON m.target_type = 'mci_business_group'
           AND m.target_id = g.id
        LEFT JOIN mci_business_branches b
            ON b.business_group_id = g.id
           AND b.is_primary = 1
        WHERE g.status != 'deleted'
          AND (b.city IS NULL OR TRIM(b.city) = '' OR LOWER(TRIM(b.city)) = 'unknown')
    ")->fetchColumn();

    $missingPhone = (int)$mci->query("
        SELECT COUNT(DISTINCT g.id)
        FROM mci_business_groups g
        INNER JOIN mci_wp_import_map m
            ON m.target_type = 'mci_business_group'
           AND m.target_id = g.id
        LEFT JOIN mci_business_branches b
            ON b.business_group_id = g.id
           AND b.is_primary = 1
        WHERE g.status != 'deleted'
          AND (b.phone_primary IS NULL OR TRIM(b.phone_primary) = '')
    ")->fetchColumn();

    $missingWebsite = (int)$mci->query("
        SELECT COUNT(DISTINCT g.id)
        FROM mci_business_groups g
        INNER JOIN mci_wp_import_map m
            ON m.target_type = 'mci_business_group'
           AND m.target_id = g.id
        LEFT JOIN mci_business_branches b
            ON b.business_group_id = g.id
           AND b.is_primary = 1
        WHERE g.status != 'deleted'
          AND (COALESCE(NULLIF(TRIM(g.website_url), ''), NULLIF(TRIM(b.website), '')) IS NULL)
    ")->fetchColumn();

    $missingSubcategory = (int)$mci->query("
        SELECT COUNT(DISTINCT g.id)
        FROM mci_business_groups g
        INNER JOIN mci_wp_import_map m
            ON m.target_type = 'mci_business_group'
           AND m.target_id = g.id
        LEFT JOIN mci_business_subcategories bsc
            ON bsc.business_group_id = g.id
        WHERE g.status != 'deleted'
          AND bsc.business_group_id IS NULL
    ")->fetchColumn();

    $sampleStmt = $mci->query("
        SELECT DISTINCT g.id, g.name, g.slug
        FROM mci_business_groups g
        INNER JOIN mci_wp_import_map m
            ON m.target_type = 'mci_business_group'
           AND m.target_id = g.id
        LEFT JOIN mci_business_branches b
            ON b.business_group_id = g.id
           AND b.is_primary = 1
        WHERE g.status != 'deleted'
          AND (b.city IS NULL OR TRIM(b.city) = '' OR LOWER(TRIM(b.city)) = 'unknown')
        ORDER BY g.created_at DESC
        LIMIT 20
    ");
    $sampleMissingCity = $sampleStmt->fetchAll();

    $sampleStmt2 = $mci->query("
        SELECT DISTINCT g.id, g.name, g.slug
        FROM mci_business_groups g
        INNER JOIN mci_wp_import_map m
            ON m.target_type = 'mci_business_group'
           AND m.target_id = g.id
        LEFT JOIN mci_business_subcategories bsc
            ON bsc.business_group_id = g.id
        WHERE g.status != 'deleted'
          AND bsc.business_group_id IS NULL
        ORDER BY g.created_at DESC
        LIMIT 20
    ");
    $sampleMissingSub = $sampleStmt2->fetchAll();

    $summary = [
        'imported_total' => $importedTotal,
        'missing_address' => $missingAddress,
        'missing_city' => $missingCity,
        'missing_phone' => $missingPhone,
        'missing_website' => $missingWebsite,
        'missing_subcategory' => $missingSubcategory,
    ];
    write_report($summary, [
        'Sample Missing City Rows' => $sampleMissingCity,
        'Sample Missing Subcategory Rows' => $sampleMissingSub,
    ]);

    echo "Audit complete.\n";
    foreach ($summary as $k => $v) {
        echo "- {$k}: {$v}\n";
    }
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Audit failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

