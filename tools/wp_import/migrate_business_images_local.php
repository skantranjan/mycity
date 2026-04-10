<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

require_once dirname(__DIR__, 2) . '/includes/mci_load_env.php';
mci_load_dotenv();

function arg_has(array $argv, string $flag): bool
{
    return in_array($flag, $argv, true);
}

function arg_value(array $argv, string $prefix): ?string
{
    foreach ($argv as $arg) {
        if (str_starts_with($arg, $prefix)) {
            return substr($arg, strlen($prefix));
        }
    }
    return null;
}

function env_req(string $key): string
{
    $v = getenv($key);
    if (!is_string($v) || trim($v) === '') {
        throw new RuntimeException("Missing required env var: {$key}");
    }
    return trim($v);
}

function pdo_mci(): PDO
{
    $host = env_req('MCI_DB_HOST');
    $name = env_req('MCI_DB_NAME');
    $user = env_req('MCI_DB_USER');
    $pass = env_req('MCI_DB_PASS');
    return new PDO(
        "mysql:host={$host};dbname={$name};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

function ensure_log_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mci_image_migration_logs (
            id char(36) NOT NULL,
            run_id char(36) NOT NULL,
            source_table varchar(64) NOT NULL,
            row_id char(36) NOT NULL,
            column_name varchar(64) NOT NULL,
            business_group_id char(36) NOT NULL,
            source_url varchar(1024) DEFAULT NULL,
            target_path varchar(1024) DEFAULT NULL,
            status enum('planned','downloaded','reused_file','updated_db','skipped_local','skipped_empty','failed') NOT NULL,
            error_message varchar(500) DEFAULT NULL,
            created_at datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
            PRIMARY KEY (id),
            KEY idx_mci_imgmig_run (run_id),
            KEY idx_mci_imgmig_group (business_group_id),
            KEY idx_mci_imgmig_status (status),
            UNIQUE KEY uniq_mci_imgmig_scope (source_table, row_id, column_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function uuid_v4(): string
{
    $d = random_bytes(16);
    $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
    $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
    $h = bin2hex($d);
    return sprintf('%s-%s-%s-%s-%s', substr($h, 0, 8), substr($h, 8, 4), substr($h, 12, 4), substr($h, 16, 4), substr($h, 20, 12));
}

function is_local_path(?string $v): bool
{
    if (!is_string($v)) {
        return false;
    }
    $s = trim($v);
    return $s !== '' && str_starts_with($s, '/storage/uploads/');
}

function is_remote_url(?string $v): bool
{
    if (!is_string($v)) {
        return false;
    }
    $s = trim($v);
    return (bool)preg_match('#^https?://#i', $s);
}

function ext_from_content_type(?string $ct): string
{
    $map = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $ct = strtolower(trim((string)$ct));
    return $map[$ct] ?? 'jpg';
}

function infer_ext(string $url, ?string $ct): string
{
    $path = parse_url($url, PHP_URL_PATH);
    $ext = is_string($path) ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : '';
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        return $ext === 'jpeg' ? 'jpg' : $ext;
    }
    return ext_from_content_type($ct);
}

function download_image(string $url, int $timeoutSec = 25): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => $timeoutSec,
        CURLOPT_USERAGENT => 'MyCityInfoImageMigrator/1.0',
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $body = curl_exec($ch);
    $err = curl_error($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ct = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if ($body === false || $err !== '') {
        return ['ok' => false, 'error' => $err !== '' ? $err : 'download_failed'];
    }
    if ($http < 200 || $http >= 300) {
        return ['ok' => false, 'error' => "http_{$http}"];
    }
    if ($body === '') {
        return ['ok' => false, 'error' => 'empty_body'];
    }
    return ['ok' => true, 'bytes' => $body, 'content_type' => $ct];
}

function write_log(PDO $pdo, array $row): void
{
    $stmt = $pdo->prepare("
        INSERT INTO mci_image_migration_logs
          (id, run_id, source_table, row_id, column_name, business_group_id, source_url, target_path, status, error_message)
        VALUES
          (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          run_id=VALUES(run_id),
          business_group_id=VALUES(business_group_id),
          source_url=VALUES(source_url),
          target_path=VALUES(target_path),
          status=VALUES(status),
          error_message=VALUES(error_message)
    ");
    $stmt->execute([
        uuid_v4(),
        $row['run_id'],
        $row['source_table'],
        $row['row_id'],
        $row['column_name'],
        $row['business_group_id'],
        $row['source_url'],
        $row['target_path'],
        $row['status'],
        $row['error_message'],
    ]);
}

function migrate_one_url(
    PDO $pdo,
    string $runId,
    bool $apply,
    string $projectRoot,
    string $sourceTable,
    string $rowId,
    string $groupId,
    string $columnName,
    string $folder,
    ?string $value
): array {
    $value = is_string($value) ? trim($value) : '';
    if ($value === '') {
        write_log($pdo, [
            'run_id' => $runId, 'source_table' => $sourceTable, 'row_id' => $rowId, 'column_name' => $columnName,
            'business_group_id' => $groupId, 'source_url' => null, 'target_path' => null, 'status' => 'skipped_empty', 'error_message' => null,
        ]);
        return ['status' => 'skipped_empty', 'new_path' => null];
    }
    if (is_local_path($value)) {
        write_log($pdo, [
            'run_id' => $runId, 'source_table' => $sourceTable, 'row_id' => $rowId, 'column_name' => $columnName,
            'business_group_id' => $groupId, 'source_url' => $value, 'target_path' => $value, 'status' => 'skipped_local', 'error_message' => null,
        ]);
        return ['status' => 'skipped_local', 'new_path' => $value];
    }
    if (!is_remote_url($value)) {
        write_log($pdo, [
            'run_id' => $runId, 'source_table' => $sourceTable, 'row_id' => $rowId, 'column_name' => $columnName,
            'business_group_id' => $groupId, 'source_url' => $value, 'target_path' => null, 'status' => 'failed', 'error_message' => 'not_remote_url',
        ]);
        return ['status' => 'failed', 'new_path' => null];
    }

    $digest = sha1($value);
    $targetDirFs = $projectRoot . '/storage/uploads/businesses/' . $groupId . '/' . $folder;
    $targetBaseWeb = '/storage/uploads/businesses/' . $groupId . '/' . $folder;
    if ($apply && !is_dir($targetDirFs)) {
        mkdir($targetDirFs, 0755, true);
    }

    $dl = download_image($value);
    if (!$dl['ok']) {
        write_log($pdo, [
            'run_id' => $runId, 'source_table' => $sourceTable, 'row_id' => $rowId, 'column_name' => $columnName,
            'business_group_id' => $groupId, 'source_url' => $value, 'target_path' => null, 'status' => 'failed', 'error_message' => (string)$dl['error'],
        ]);
        return ['status' => 'failed', 'new_path' => null];
    }

    $ext = infer_ext($value, (string)($dl['content_type'] ?? ''));
    $filename = $digest . '.' . $ext;
    $targetFs = $targetDirFs . '/' . $filename;
    $targetWeb = $targetBaseWeb . '/' . $filename;

    if ($apply) {
        if (!is_file($targetFs)) {
            file_put_contents($targetFs, $dl['bytes']);
            $status = 'downloaded';
        } else {
            $status = 'reused_file';
        }
        write_log($pdo, [
            'run_id' => $runId, 'source_table' => $sourceTable, 'row_id' => $rowId, 'column_name' => $columnName,
            'business_group_id' => $groupId, 'source_url' => $value, 'target_path' => $targetWeb, 'status' => $status, 'error_message' => null,
        ]);
    } else {
        write_log($pdo, [
            'run_id' => $runId, 'source_table' => $sourceTable, 'row_id' => $rowId, 'column_name' => $columnName,
            'business_group_id' => $groupId, 'source_url' => $value, 'target_path' => $targetWeb, 'status' => 'planned', 'error_message' => null,
        ]);
    }

    return ['status' => $apply ? 'downloaded' : 'planned', 'new_path' => $targetWeb];
}

function inc(array &$s, string $k): void
{
    $s[$k] = ($s[$k] ?? 0) + 1;
}

try {
    $argv = $_SERVER['argv'] ?? [];
    $apply = arg_has($argv, '--apply');
    $limitRaw = arg_value($argv, '--limit=');
    $limit = ($limitRaw !== null && ctype_digit($limitRaw)) ? (int)$limitRaw : 0;

    $pdo = pdo_mci();
    ensure_log_table($pdo);
    $runId = uuid_v4();
    $projectRoot = str_replace('\\', '/', realpath(dirname(__DIR__, 2)) ?: dirname(__DIR__, 2));

    $summary = [
        'run_id' => $runId,
        'mode' => $apply ? 'apply' : 'dry-run',
        'processed' => 0,
        'planned' => 0,
        'downloaded' => 0,
        'reused_file' => 0,
        'updated_db' => 0,
        'skipped_local' => 0,
        'skipped_empty' => 0,
        'failed' => 0,
        'ambiguous_logo_banner_applied' => 0,
    ];

    // 1) Business group fields (logo/profile/banner) with ambiguity fallback for logo/banner
    $sql = "SELECT id, logo_path, profile_path, banner_path FROM mci_business_groups ORDER BY created_at ASC";
    if ($limit > 0) {
        $sql .= " LIMIT " . $limit;
    }
    $groups = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($groups as $g) {
        $groupId = (string)$g['id'];
        $new = [
            'logo_path' => (string)($g['logo_path'] ?? ''),
            'profile_path' => (string)($g['profile_path'] ?? ''),
            'banner_path' => (string)($g['banner_path'] ?? ''),
        ];

        $map = [
            'logo_path' => 'logo',
            'profile_path' => 'profile',
            'banner_path' => 'banner',
        ];
        foreach ($map as $col => $folder) {
            $r = migrate_one_url($pdo, $runId, $apply, $projectRoot, 'mci_business_groups', $groupId, $groupId, $col, $folder, (string)($g[$col] ?? ''));
            inc($summary, $r['status']);
            $summary['processed']++;
            if (is_string($r['new_path']) && $r['new_path'] !== '') {
                $new[$col] = $r['new_path'];
            }
        }

        // Ambiguity rule: if one of logo/banner resolved and the other is empty/non-local/non-remote, set both.
        $logo = $new['logo_path'];
        $banner = $new['banner_path'];
        $logoGood = is_local_path($logo);
        $bannerGood = is_local_path($banner);
        if ($logoGood xor $bannerGood) {
            $shared = $logoGood ? $logo : $banner;
            $new['logo_path'] = $shared;
            $new['banner_path'] = $shared;
            $summary['ambiguous_logo_banner_applied']++;
        }

        if ($apply) {
            $stmt = $pdo->prepare("UPDATE mci_business_groups SET logo_path=?, profile_path=?, banner_path=? WHERE id=?");
            $stmt->execute([$new['logo_path'] !== '' ? $new['logo_path'] : null, $new['profile_path'] !== '' ? $new['profile_path'] : null, $new['banner_path'] !== '' ? $new['banner_path'] : null, $groupId]);
            if ($stmt->rowCount() > 0) {
                inc($summary, 'updated_db');
            }
        }
    }

    // 2) Gallery
    $sqlG = "SELECT id, business_group_id, file_path FROM mci_business_images ORDER BY created_at ASC";
    if ($limit > 0) {
        $sqlG .= " LIMIT " . $limit;
    }
    $galleryRows = $pdo->query($sqlG)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $updGallery = $pdo->prepare("UPDATE mci_business_images SET file_path=? WHERE id=?");
    foreach ($galleryRows as $r) {
        $rowId = (string)$r['id'];
        $groupId = (string)$r['business_group_id'];
        $m = migrate_one_url($pdo, $runId, $apply, $projectRoot, 'mci_business_images', $rowId, $groupId, 'file_path', 'gallery', (string)$r['file_path']);
        inc($summary, $m['status']);
        $summary['processed']++;
        if ($apply && is_string($m['new_path']) && $m['new_path'] !== '' && $m['new_path'] !== (string)$r['file_path']) {
            $updGallery->execute([$m['new_path'], $rowId]);
            if ($updGallery->rowCount() > 0) {
                inc($summary, 'updated_db');
            }
        }
    }

    // 3) Products
    $sqlP = "SELECT id, business_group_id, image_path FROM mci_business_products ORDER BY created_at ASC";
    if ($limit > 0) {
        $sqlP .= " LIMIT " . $limit;
    }
    $productRows = $pdo->query($sqlP)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $updProduct = $pdo->prepare("UPDATE mci_business_products SET image_path=? WHERE id=?");
    foreach ($productRows as $r) {
        $rowId = (string)$r['id'];
        $groupId = (string)$r['business_group_id'];
        $m = migrate_one_url($pdo, $runId, $apply, $projectRoot, 'mci_business_products', $rowId, $groupId, 'image_path', 'products', (string)($r['image_path'] ?? ''));
        inc($summary, $m['status']);
        $summary['processed']++;
        if ($apply && is_string($m['new_path']) && $m['new_path'] !== '' && $m['new_path'] !== (string)$r['image_path']) {
            $updProduct->execute([$m['new_path'], $rowId]);
            if ($updProduct->rowCount() > 0) {
                inc($summary, 'updated_db');
            }
        }
    }

    // 4) Services
    $sqlS = "SELECT id, business_group_id, image_path FROM mci_business_services ORDER BY created_at ASC";
    if ($limit > 0) {
        $sqlS .= " LIMIT " . $limit;
    }
    $serviceRows = $pdo->query($sqlS)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $updService = $pdo->prepare("UPDATE mci_business_services SET image_path=? WHERE id=?");
    foreach ($serviceRows as $r) {
        $rowId = (string)$r['id'];
        $groupId = (string)$r['business_group_id'];
        $m = migrate_one_url($pdo, $runId, $apply, $projectRoot, 'mci_business_services', $rowId, $groupId, 'image_path', 'services', (string)($r['image_path'] ?? ''));
        inc($summary, $m['status']);
        $summary['processed']++;
        if ($apply && is_string($m['new_path']) && $m['new_path'] !== '' && $m['new_path'] !== (string)$r['image_path']) {
            $updService->execute([$m['new_path'], $rowId]);
            if ($updService->rowCount() > 0) {
                inc($summary, 'updated_db');
            }
        }
    }

    $reportDir = $projectRoot . '/docs/import';
    if (!is_dir($reportDir)) {
        mkdir($reportDir, 0777, true);
    }
    $reportPath = $reportDir . '/business-image-local-migration-report.md';
    $lines = [];
    $lines[] = '# Business Image Local Migration Report';
    $lines[] = '';
    $lines[] = '- run_id: `' . $summary['run_id'] . '`';
    $lines[] = '- mode: `' . $summary['mode'] . '`';
    foreach ($summary as $k => $v) {
        if (in_array($k, ['run_id', 'mode'], true)) {
            continue;
        }
        $lines[] = '- ' . $k . ': `' . (string)$v . '`';
    }
    file_put_contents($reportPath, implode(PHP_EOL, $lines) . PHP_EOL);

    echo "Image migration completed.\n";
    echo "run_id={$summary['run_id']}\n";
    echo "mode={$summary['mode']}\n";
    echo "processed={$summary['processed']} updated_db={$summary['updated_db']} failed={$summary['failed']}\n";
    echo "report={$reportPath}\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Image migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

