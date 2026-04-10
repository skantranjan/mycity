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
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    $rows = [];
    $rows['groups_logo'] = $pdo->query("SELECT id, logo_path FROM mci_business_groups WHERE logo_path REGEXP '^https?://'")->fetchAll() ?: [];
    $rows['groups_banner'] = $pdo->query("SELECT id, banner_path FROM mci_business_groups WHERE banner_path REGEXP '^https?://'")->fetchAll() ?: [];
    $rows['groups_profile'] = $pdo->query("SELECT id, profile_path FROM mci_business_groups WHERE profile_path REGEXP '^https?://'")->fetchAll() ?: [];
    $rows['gallery'] = $pdo->query("SELECT id, business_group_id, file_path FROM mci_business_images WHERE file_path REGEXP '^https?://'")->fetchAll() ?: [];
    $rows['products'] = $pdo->query("SELECT id, business_group_id, image_path FROM mci_business_products WHERE image_path REGEXP '^https?://'")->fetchAll() ?: [];
    $rows['services'] = $pdo->query("SELECT id, business_group_id, image_path FROM mci_business_services WHERE image_path REGEXP '^https?://'")->fetchAll() ?: [];

    echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

