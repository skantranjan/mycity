<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/mci_load_env.php';
mci_load_dotenv();

function env_req(string $k): string
{
    $v = getenv($k);
    if (!is_string($v) || trim($v) === '') {
        throw new RuntimeException("Missing env: {$k}");
    }
    return trim($v);
}

try {
    $pdo = new PDO(
        "mysql:host=" . env_req('MCI_DB_HOST') . ";dbname=" . env_req('MCI_DB_NAME') . ";charset=utf8mb4",
        env_req('MCI_DB_USER'),
        env_req('MCI_DB_PASS'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $projectRoot = str_replace('\\', '/', realpath(dirname(__DIR__, 2)) ?: dirname(__DIR__, 2));

    $checks = [
        'mci_business_groups.logo_path' => "SELECT COUNT(*) FROM mci_business_groups WHERE logo_path REGEXP '^https?://'",
        'mci_business_groups.profile_path' => "SELECT COUNT(*) FROM mci_business_groups WHERE profile_path REGEXP '^https?://'",
        'mci_business_groups.banner_path' => "SELECT COUNT(*) FROM mci_business_groups WHERE banner_path REGEXP '^https?://'",
        'mci_business_images.file_path' => "SELECT COUNT(*) FROM mci_business_images WHERE file_path REGEXP '^https?://'",
        'mci_business_products.image_path' => "SELECT COUNT(*) FROM mci_business_products WHERE image_path REGEXP '^https?://'",
        'mci_business_services.image_path' => "SELECT COUNT(*) FROM mci_business_services WHERE image_path REGEXP '^https?://'",
    ];

    $external = [];
    foreach ($checks as $k => $sql) {
        $external[$k] = (int)$pdo->query($sql)->fetchColumn();
    }

    $sample = $pdo->query("
        SELECT id, name, logo_path, banner_path
        FROM mci_business_groups
        WHERE logo_path LIKE '/storage/uploads/%' OR banner_path LIKE '/storage/uploads/%'
        ORDER BY updated_at DESC, created_at DESC
        LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $sampleExist = 0;
    $sampleTotal = 0;
    foreach ($sample as $row) {
        foreach (['logo_path', 'banner_path'] as $col) {
            $p = (string)($row[$col] ?? '');
            if ($p === '' || !str_starts_with($p, '/storage/uploads/')) {
                continue;
            }
            $sampleTotal++;
            $fs = $projectRoot . $p;
            if (is_file($fs)) {
                $sampleExist++;
            }
        }
    }

    echo json_encode([
        'external_url_counts' => $external,
        'sample_file_checks' => [
            'checked' => $sampleTotal,
            'existing' => $sampleExist,
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

