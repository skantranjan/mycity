<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

require_once dirname(__DIR__, 2) . '/includes/mci_load_env.php';
require_once dirname(__DIR__, 2) . '/api/v1/lib/uuid.php';
mci_load_dotenv();

const DEFAULT_LISTING_TYPES = ['business', 'listing', 'place'];
const DEFAULT_CATEGORY_TAXONOMIES = ['business_category', 'category'];
const ROLE_SUBSCRIBER = 'subscriber';
const ROLE_SUPER_ADMIN = 'super_admin';

function arg_flag(array $argv, string $flag): bool
{
    return in_array($flag, $argv, true);
}

function env_opt(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if (!is_string($value) || trim($value) === '') {
        return $default;
    }
    return trim($value);
}

function env_req(string $key): string
{
    $value = env_opt($key);
    if ($value === null) {
        throw new RuntimeException("Missing required env var: {$key}");
    }
    return $value;
}

function parse_list_env(string $key, array $default): array
{
    $raw = env_opt($key);
    if ($raw === null) {
        return $default;
    }
    $parts = array_values(array_filter(array_map('trim', explode(',', $raw)), static fn(string $s): bool => $s !== ''));
    return $parts ?: $default;
}

function pdo_mysql(string $host, string $db, string $user, string $pass, ?string $port = null): PDO
{
    $portPart = ($port !== null && $port !== '') ? ';port=' . (int)$port : '';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
        // Keep longer sessions for large one-time imports.
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET SESSION wait_timeout=28800, interactive_timeout=28800";
    }

    return new PDO(
        "mysql:host={$host}{$portPart};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        $options
    );
}

function detect_wp_prefix(PDO $wp): string
{
    $configured = env_opt('WP_TABLE_PREFIX');
    if ($configured !== null) {
        return $configured;
    }
    $rows = $wp->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);
    foreach ($rows as $row) {
        $table = (string)($row[0] ?? '');
        if (str_ends_with($table, 'posts')) {
            return substr($table, 0, -5);
        }
    }
    throw new RuntimeException('Could not detect WP table prefix. Set WP_TABLE_PREFIX.');
}

function sql_in(array $items): string
{
    return implode(',', array_fill(0, count($items), '?'));
}

function fetch_rows(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function fetch_one(PDO $pdo, string $sql, array $params = []): ?array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function chunked(array $items, int $size = 500): array
{
    if (empty($items)) {
        return [];
    }
    return array_chunk($items, max(1, $size));
}

function ensure_import_tables(PDO $mci): void
{
    $mci->exec("
        CREATE TABLE IF NOT EXISTS mci_wp_import_runs (
            id char(36) NOT NULL,
            started_at datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
            finished_at datetime(6) NULL,
            status enum('running','done','failed') NOT NULL DEFAULT 'running',
            is_dry_run tinyint(1) NOT NULL DEFAULT 1,
            summary_json longtext NULL,
            error_message varchar(500) NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $mci->exec("
        CREATE TABLE IF NOT EXISTS mci_wp_import_logs (
            id char(36) NOT NULL,
            run_id char(36) NOT NULL,
            scope varchar(64) NOT NULL,
            source_ref varchar(128) NULL,
            level enum('info','warn','error') NOT NULL DEFAULT 'info',
            message varchar(500) NOT NULL,
            payload_json longtext NULL,
            created_at datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
            PRIMARY KEY (id),
            KEY idx_mci_wp_import_logs_run_id (run_id),
            CONSTRAINT fk_mci_wp_import_logs_run_id
              FOREIGN KEY (run_id) REFERENCES mci_wp_import_runs(id)
              ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $mci->exec("
        CREATE TABLE IF NOT EXISTS mci_wp_import_map (
            id char(36) NOT NULL,
            source_type varchar(50) NOT NULL,
            source_id bigint unsigned NOT NULL,
            target_type varchar(50) NOT NULL,
            target_id char(36) NOT NULL,
            created_at datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
            PRIMARY KEY (id),
            UNIQUE KEY uniq_mci_wp_import_map_source_target (source_type, source_id, target_type),
            KEY idx_mci_wp_import_map_target (target_type, target_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function log_import(PDO $mci, string $runId, string $scope, string $level, string $message, ?string $sourceRef = null, ?array $payload = null): void
{
    $stmt = $mci->prepare('
        INSERT INTO mci_wp_import_logs (id, run_id, scope, source_ref, level, message, payload_json)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        api_uuid_v4(),
        $runId,
        $scope,
        $sourceRef,
        $level,
        substr($message, 0, 500),
        $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
    ]);
}

function role_id(PDO $mci, string $shortName): int
{
    $row = fetch_one($mci, 'SELECT id FROM mci_roles WHERE short_name = ? LIMIT 1', [$shortName]);
    if (!$row) {
        throw new RuntimeException("Missing role in mci_roles: {$shortName}");
    }
    return (int)$row['id'];
}

function first_non_empty(array $meta, array $candidates): ?string
{
    foreach ($candidates as $key) {
        if (!array_key_exists($key, $meta)) {
            continue;
        }
        $v = trim((string)$meta[$key]);
        if ($v !== '') {
            return $v;
        }
    }
    return null;
}

function next_unique_slug(PDO $mci, string $table, string $slug, string $idCol = 'id', ?string $ignoreId = null): string
{
    $base = strtolower(trim(preg_replace('/[^a-z0-9-]+/i', '-', $slug) ?? ''));
    $base = trim($base, '-');
    if ($base === '') {
        $base = 'item';
    }

    $candidate = $base;
    $i = 2;
    while (true) {
        $sql = "SELECT {$idCol} FROM {$table} WHERE slug = ? LIMIT 1";
        $row = fetch_one($mci, $sql, [$candidate]);
        if (!$row || ($ignoreId !== null && (string)$row[$idCol] === $ignoreId)) {
            return $candidate;
        }
        $candidate = $base . '-' . $i;
        $i++;
    }
}

function maybe_copy_media(string $sourceUrl, string $uploadsPath, string $targetRoot): ?string
{
    $path = parse_url($sourceUrl, PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        return null;
    }
    $needle = '/uploads/';
    $pos = strpos($path, $needle);
    if ($pos === false) {
        return null;
    }
    $rel = substr($path, $pos + strlen($needle));
    $rel = str_replace(['..\\', '../'], '', $rel);
    $source = rtrim($uploadsPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($source)) {
        return null;
    }

    $destDir = rtrim($targetRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . dirname($rel);
    if (!is_dir($destDir)) {
        mkdir($destDir, 0777, true);
    }
    $dest = rtrim($targetRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $rel;
    if (!is_file($dest)) {
        copy($source, $dest);
    }
    return str_replace('\\', '/', 'assets/uploads/wp-import/' . $rel);
}

function parse_hours_payload(?string $raw): array
{
    if ($raw === null || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }
    $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
    $out = [];
    foreach ($days as $day) {
        $d = $decoded[$day] ?? null;
        if (!is_array($d)) {
            continue;
        }
        $isClosed = !empty($d['closed']);
        $out[] = [
            'day' => $day,
            'is_closed' => $isClosed ? 1 : 0,
            'opens_at' => $isClosed ? null : ($d['open'] ?? null),
            'closes_at' => $isClosed ? null : ($d['close'] ?? null),
            'opens_at_2' => $isClosed ? null : ($d['open2'] ?? null),
            'closes_at_2' => $isClosed ? null : ($d['close2'] ?? null),
        ];
    }
    return $out;
}

function format_time_or_null(?string $value): ?string
{
    if ($value === null) {
        return null;
    }
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value) === 1) {
        return strlen($value) === 5 ? ($value . ':00') : $value;
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return null;
    }
    return gmdate('H:i:s', $ts);
}

function import_users(PDO $wp, PDO $mci, string $runId, bool $dryRun): array
{
    $subscriberRoleId = role_id($mci, ROLE_SUBSCRIBER);
    $adminRoleId = role_id($mci, ROLE_SUPER_ADMIN);
    $pfx = env_req('WP_TABLE_PREFIX');
    $users = fetch_rows($wp, 'SELECT ID, user_email, display_name, user_registered, user_status FROM ' . $pfx . 'users');

    $countInserted = 0;
    $countUpdated = 0;
    $countSkipped = 0;

    $userIds = array_map(static fn(array $u): int => (int)$u['ID'], $users);
    $profilesByUserId = [];
    if (!empty($userIds)) {
        foreach (chunked($userIds, 800) as $chunk) {
            $in = sql_in($chunk);
            $rows = fetch_rows(
                $wp,
                "SELECT user_id, meta_key, meta_value
                 FROM {$pfx}usermeta
                 WHERE user_id IN ({$in}) AND meta_key IN ('first_name','last_name')",
                $chunk
            );
            foreach ($rows as $r) {
                $uid = (int)$r['user_id'];
                $profilesByUserId[$uid][(string)$r['meta_key']] = (string)$r['meta_value'];
            }
        }
    }

    $validEmails = [];
    foreach ($users as $u) {
        $email = strtolower(trim((string)$u['user_email']));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validEmails[] = $email;
        }
    }
    $validEmails = array_values(array_unique($validEmails));
    $existingUsersByEmail = [];
    if (!empty($validEmails)) {
        foreach (chunked($validEmails, 500) as $chunk) {
            $in = sql_in($chunk);
            $rows = fetch_rows($mci, "SELECT id, email FROM mci_users WHERE email IN ({$in})", $chunk);
            foreach ($rows as $r) {
                $existingUsersByEmail[strtolower((string)$r['email'])] = (string)$r['id'];
            }
        }
    }

    $insertUser = $mci->prepare('
        INSERT INTO mci_users (id, email, role_id, password_hash, display_name, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    $updateUser = $mci->prepare('
        UPDATE mci_users
        SET display_name = ?, status = ?, role_id = ?
        WHERE id = ?
    ');
    $upsertProfile = $mci->prepare('
        INSERT INTO mci_userprofiles (id, userid, first_name, last_name, created_at)
        VALUES (?, ?, ?, ?, NOW(6))
        ON DUPLICATE KEY UPDATE
            first_name = VALUES(first_name),
            last_name = VALUES(last_name),
            updated_at = NOW(6)
    ');
    $sharedTempHash = $dryRun ? '' : password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

    foreach ($users as $u) {
        $email = strtolower(trim((string)$u['user_email']));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $countSkipped++;
            log_import($mci, $runId, 'users', 'warn', 'Skipped invalid email', (string)$u['ID'], ['email' => $u['user_email']]);
            continue;
        }

        $role = ((int)$u['user_status'] === 0) ? $subscriberRoleId : $adminRoleId;
        $status = ((int)$u['user_status'] === 0) ? 'active' : 'inactive';
        $display = trim((string)$u['display_name']) !== '' ? trim((string)$u['display_name']) : null;

        $meta = $profilesByUserId[(int)$u['ID']] ?? [];
        $firstName = trim((string)($meta['first_name'] ?? '')) ?: null;
        $lastName = trim((string)($meta['last_name'] ?? '')) ?: null;

        $existingId = $existingUsersByEmail[$email] ?? null;
        if ($existingId !== null) {
            if (!$dryRun) {
                $updateUser->execute([$display, $status, $role, $existingId]);
                $upsertProfile->execute([api_uuid_v4(), $existingId, $firstName, $lastName]);
            }
            $countUpdated++;
            continue;
        }

        if (!$dryRun) {
            $id = api_uuid_v4();
            $createdAt = trim((string)$u['user_registered']) !== '' ? (string)$u['user_registered'] : gmdate('Y-m-d H:i:s');
            $insertUser->execute([$id, $email, $role, $sharedTempHash, $display, $status, $createdAt]);
            $upsertProfile->execute([api_uuid_v4(), $id, $firstName, $lastName]);
        }
        $countInserted++;
    }

    return ['inserted' => $countInserted, 'updated' => $countUpdated, 'skipped' => $countSkipped];
}

function import_categories(PDO $wp, PDO $mci, string $runId, bool $dryRun, array $taxonomies): array
{
    $pfx = env_req('WP_TABLE_PREFIX');
    $in = sql_in($taxonomies);
    $rows = fetch_rows(
        $wp,
        "SELECT tt.term_taxonomy_id, tt.parent, tt.taxonomy, t.term_id, t.name, t.slug
         FROM {$pfx}term_taxonomy tt
         INNER JOIN {$pfx}terms t ON t.term_id = tt.term_id
         WHERE tt.taxonomy IN ({$in})
         ORDER BY tt.parent ASC, t.term_id ASC",
        $taxonomies
    );

    $byTaxId = [];
    foreach ($rows as $r) {
        $byTaxId[(int)$r['term_taxonomy_id']] = $r;
    }

    $map = [];
    $inserted = 0;
    $updated = 0;
    $insertStmt = $mci->prepare('
        INSERT INTO mci_categories (parent_id, name, slug, page_title, meta_keywords, meta_description, description, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(6))
    ');
    $updateStmt = $mci->prepare('
        UPDATE mci_categories
        SET parent_id=?, name=?, page_title=?, meta_keywords=?, meta_description=?
        WHERE id=?
    ');

    foreach ($rows as $r) {
        $termId = (int)$r['term_id'];
        $name = trim((string)$r['name']);
        if ($name === '') {
            continue;
        }
        $parentTaxId = (int)$r['parent'];
        $parentId = $parentTaxId > 0 && isset($map[$parentTaxId]) ? $map[$parentTaxId] : null;
        $slug = next_unique_slug($mci, 'mci_categories', (string)$r['slug']);

        $existing = fetch_one($mci, 'SELECT id FROM mci_categories WHERE slug = ? LIMIT 1', [$slug]);
        if ($existing) {
            if (!$dryRun) {
                $updateStmt->execute([$parentId, $name, $name, null, null, (int)$existing['id']]);
            }
            $updated++;
            $map[(int)$r['term_taxonomy_id']] = (int)$existing['id'];
            continue;
        }

        if (!$dryRun) {
            $insertStmt->execute([$parentId, $name, $slug, $name, null, null, null]);
            $map[(int)$r['term_taxonomy_id']] = (int)$mci->lastInsertId();
        }
        $inserted++;

        if (!$dryRun) {
            $mci->prepare('
                INSERT INTO mci_wp_import_map (id, source_type, source_id, target_type, target_id)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE target_id = VALUES(target_id)
            ')->execute([api_uuid_v4(), 'wp_term', $termId, 'mci_category', (string)$map[(int)$r['term_taxonomy_id']]]);
        }
    }

    log_import($mci, $runId, 'categories', 'info', 'Categories imported', null, ['inserted' => $inserted, 'updated' => $updated]);
    return ['inserted' => $inserted, 'updated' => $updated];
}

function import_businesses(PDO $wp, PDO $mci, string $runId, bool $dryRun, array $listingPostTypes): array
{
    $pfx = env_req('WP_TABLE_PREFIX');
    $typesSql = sql_in($listingPostTypes);
    $posts = fetch_rows(
        $wp,
        "SELECT ID, post_title, post_name, post_content, post_type, post_date
         FROM {$pfx}posts
         WHERE post_type IN ({$typesSql}) AND post_status='publish'
         ORDER BY ID ASC",
        $listingPostTypes
    );

    $systemUser = fetch_one(
        $mci,
        "SELECT u.id
         FROM mci_users u
         INNER JOIN mci_roles r ON r.id = u.role_id
         WHERE r.short_name IN ('super_admin','co_admin')
         ORDER BY u.created_at ASC
         LIMIT 1"
    );
    if (!$systemUser) {
        throw new RuntimeException('At least one CP admin user is required in mci_users before business import.');
    }
    $actorId = (string)$systemUser['id'];

    $defaultCategory = fetch_one($mci, 'SELECT id FROM mci_categories WHERE parent_id IS NULL ORDER BY id ASC LIMIT 1');
    if (!$defaultCategory) {
        throw new RuntimeException('At least one top-level category is required in mci_categories before business import.');
    }
    $defaultCategoryId = (int)$defaultCategory['id'];

    $postIds = array_map(static fn(array $p): int => (int)$p['ID'], $posts);
    $metaByPostId = [];
    $termIdsByPostId = [];
    if (!empty($postIds)) {
        foreach (chunked($postIds, 500) as $chunk) {
            $in = sql_in($chunk);
            $metaRows = fetch_rows($wp, "SELECT post_id, meta_key, meta_value FROM {$pfx}postmeta WHERE post_id IN ({$in})", $chunk);
            foreach ($metaRows as $mr) {
                $metaByPostId[(int)$mr['post_id']][(string)$mr['meta_key']] = (string)$mr['meta_value'];
            }

            $termRows = fetch_rows(
                $wp,
                "SELECT tr.object_id, tt.term_id
                 FROM {$pfx}term_relationships tr
                 INNER JOIN {$pfx}term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
                 WHERE tr.object_id IN ({$in})
                   AND tt.taxonomy IN ('listing-category','business_category','category')
                 ORDER BY tt.term_taxonomy_id ASC",
                $chunk
            );
            foreach ($termRows as $tr) {
                $termIdsByPostId[(int)$tr['object_id']][] = (int)$tr['term_id'];
            }
        }
    }

    $allTermIds = [];
    foreach ($termIdsByPostId as $tids) {
        foreach ($tids as $tid) {
            $allTermIds[$tid] = true;
        }
    }
    $termToCategoryMap = [];
    if (!empty($allTermIds)) {
        $termIds = array_map('intval', array_keys($allTermIds));
        foreach (chunked($termIds, 500) as $chunk) {
            $in = sql_in($chunk);
            $rows = fetch_rows(
                $mci,
                "SELECT source_id, target_id
                 FROM mci_wp_import_map
                 WHERE source_type='wp_term' AND target_type='mci_category' AND source_id IN ({$in})",
                $chunk
            );
            foreach ($rows as $r) {
                $termToCategoryMap[(int)$r['source_id']] = (int)$r['target_id'];
            }
        }
    }

    $categoryIds = array_values(array_unique(array_map('intval', array_values($termToCategoryMap))));
    $categoryParentMap = [];
    if (!empty($categoryIds)) {
        foreach (chunked($categoryIds, 500) as $chunk) {
            $in = sql_in($chunk);
            $rows = fetch_rows(
                $mci,
                "SELECT id, parent_id FROM mci_categories WHERE id IN ({$in})",
                $chunk
            );
            foreach ($rows as $r) {
                $categoryParentMap[(int)$r['id']] = isset($r['parent_id']) ? (int)$r['parent_id'] : null;
            }
        }
    }

    $mappedPostToGroup = [];
    if (!empty($postIds)) {
        foreach (chunked($postIds, 500) as $chunk) {
            $in = sql_in($chunk);
            $rows = fetch_rows(
                $mci,
                "SELECT source_id, target_id
                 FROM mci_wp_import_map
                 WHERE source_type='wp_post' AND target_type='mci_business_group' AND source_id IN ({$in})",
                $chunk
            );
            foreach ($rows as $r) {
                $mappedPostToGroup[(int)$r['source_id']] = (string)$r['target_id'];
            }
        }
    }

    $existingGroupBySlug = [];
    $postSlugs = [];
    foreach ($posts as $post) {
        $slug = (string)$post['post_name'];
        if ($slug !== '') {
            $postSlugs[$slug] = true;
        }
    }
    $slugValues = array_keys($postSlugs);
    if (!empty($slugValues)) {
        foreach (chunked($slugValues, 400) as $chunk) {
            $in = sql_in($chunk);
            $rows = fetch_rows($mci, "SELECT id, slug FROM mci_business_groups WHERE slug IN ({$in})", $chunk);
            foreach ($rows as $r) {
                $existingGroupBySlug[(string)$r['slug']] = (string)$r['id'];
            }
        }
    }

    $attachmentIdsIndex = [];
    foreach ($metaByPostId as $meta) {
        foreach (['_thumbnail_id', 'featured_image_id'] as $k) {
            if (!empty($meta[$k]) && ctype_digit((string)$meta[$k])) {
                $attachmentIdsIndex[(int)$meta[$k]] = true;
            }
        }
        foreach (['gallery_ids', 'gallery_image_ids'] as $k) {
            if (empty($meta[$k])) {
                continue;
            }
            foreach (explode(',', (string)$meta[$k]) as $idPart) {
                $idPart = trim($idPart);
                if (ctype_digit($idPart)) {
                    $attachmentIdsIndex[(int)$idPart] = true;
                }
            }
        }
    }
    $attachmentGuidById = [];
    $attachmentIds = array_map('intval', array_keys($attachmentIdsIndex));
    if (!empty($attachmentIds)) {
        foreach (chunked($attachmentIds, 500) as $chunk) {
            $in = sql_in($chunk);
            $rows = fetch_rows($wp, "SELECT ID, guid FROM {$pfx}posts WHERE post_type='attachment' AND ID IN ({$in})", $chunk);
            foreach ($rows as $r) {
                $attachmentGuidById[(int)$r['ID']] = (string)$r['guid'];
            }
        }
    }

    $groupInsert = $mci->prepare('
        INSERT INTO mci_business_groups
          (id, name, slug, description, website_url, parent_category_id, page_title, meta_keywords, meta_description,
           status, added_by_role, added_by_user_id, created_by_user_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $groupUpdate = $mci->prepare('
        UPDATE mci_business_groups
        SET name=?, description=?, website_url=?, parent_category_id=?, page_title=?, meta_keywords=?, meta_description=?, updated_by_user_id=?
        WHERE id=?
    ');
    $branchInsert = $mci->prepare('
        INSERT INTO mci_business_branches
          (id, business_group_id, slug, address_line1, city, state, country, pincode, latitude, longitude, phone_primary, website, is_primary, status, created_by_user_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, \'active\', ?)
    ');
    $hoursUpsert = $mci->prepare('
        INSERT INTO mci_business_branch_hours
          (id, branch_id, day_of_week, opens_at, closes_at, opens_at_2, closes_at_2, is_closed, created_by_user_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          opens_at=VALUES(opens_at), closes_at=VALUES(closes_at), opens_at_2=VALUES(opens_at_2), closes_at_2=VALUES(closes_at_2),
          is_closed=VALUES(is_closed), updated_by_user_id=VALUES(created_by_user_id)
    ');
    $imageInsert = $mci->prepare('
        INSERT INTO mci_business_images
          (id, business_group_id, file_path, sort_order, created_by_user_id)
        VALUES (?, ?, ?, ?, ?)
    ');
    $subcategoryInsert = $mci->prepare('
        INSERT IGNORE INTO mci_business_subcategories
          (id, business_group_id, category_id, created_by_user_id)
        VALUES (?, ?, ?, ?)
    ');

    $uploadsPath = env_opt('WP_UPLOADS_PATH');
    $mediaTarget = dirname(__DIR__, 2) . '/assets/uploads/wp-import';
    $inserted = 0;
    $updated = 0;
    $skipped = 0;

    foreach ($posts as $post) {
        $sourceId = (int)$post['ID'];
        $name = trim((string)$post['post_title']);
        if ($name === '') {
            $skipped++;
            log_import($mci, $runId, 'businesses', 'warn', 'Skipped empty title', (string)$sourceId);
            continue;
        }

        $meta = $metaByPostId[$sourceId] ?? [];

        $seoTitle = first_non_empty($meta, ['_yoast_wpseo_title']) ?: $name;
        $seoDesc = first_non_empty($meta, ['_yoast_wpseo_metadesc']);
        $seoKey = first_non_empty($meta, ['_yoast_wpseo_focuskw']);
        $website = first_non_empty($meta, ['website', '_website', 'url', 'business_website', 'company_website', 'contact_website']);
        $address = first_non_empty($meta, [
            'address', '_address', 'business_address', 'street_address', 'address_line_1',
            'address_line1', 'listing_address', 'location_address', '_location_address'
        ]) ?: 'Address not provided';
        $city = first_non_empty($meta, ['city', '_city', 'business_city', 'town', 'location_city', '_location_city']) ?: 'Unknown';
        $state = first_non_empty($meta, ['state', '_state', 'business_state', 'province', 'region', 'location_state']);
        $country = first_non_empty($meta, ['country', '_country', 'business_country', 'location_country']) ?: 'India';
        $pincode = first_non_empty($meta, ['pincode', 'postal_code', 'zip', '_zip', 'zipcode', 'zip_code', 'location_pincode']);
        $phone = first_non_empty($meta, ['phone', '_phone', 'mobile', 'contact_number', 'telephone', 'phone_number', '_phone_number']);
        $lat = first_non_empty($meta, ['lat', 'latitude', '_latitude', 'geo_latitude', 'map_lat']);
        $lng = first_non_empty($meta, ['lng', 'lon', 'longitude', '_longitude', 'geo_longitude', 'map_lng']);
        $hoursRaw = first_non_empty($meta, ['business_hours', '_business_hours', 'opening_hours_json']);

        $targetCategory = $defaultCategoryId;
        $subcategoryIds = [];
        foreach (($termIdsByPostId[$sourceId] ?? []) as $termId) {
            if (!isset($termToCategoryMap[$termId])) {
                continue;
            }
            $mappedCategoryId = $termToCategoryMap[$termId];
            $parentId = $categoryParentMap[$mappedCategoryId] ?? null;
            if ($parentId !== null && $parentId > 0) {
                $targetCategory = $parentId;
                $subcategoryIds[$mappedCategoryId] = true;
                continue;
            }
            if ($targetCategory === $defaultCategoryId) {
                $targetCategory = $mappedCategoryId;
            }
        }

        $existingGroupId = $mappedPostToGroup[$sourceId] ?? null;
        if ($existingGroupId === null) {
            $existingGroupId = $existingGroupBySlug[(string)$post['post_name']] ?? null;
        }

        $groupSlug = next_unique_slug($mci, 'mci_business_groups', (string)($post['post_name'] ?: $name), 'id', $existingGroupId);

        if (!$dryRun) {
            if ($existingGroupId) {
                $groupUpdate->execute([$name, (string)$post['post_content'], $website, $targetCategory, $seoTitle, $seoKey, $seoDesc, $actorId, $existingGroupId]);
                $groupId = $existingGroupId;
                $updated++;
            } else {
                $groupId = api_uuid_v4();
                $groupInsert->execute([
                    $groupId,
                    $name,
                    $groupSlug,
                    (string)$post['post_content'],
                    $website,
                    $targetCategory,
                    $seoTitle,
                    $seoKey,
                    $seoDesc,
                    'live',
                    'cp_admin',
                    $actorId,
                    $actorId,
                ]);
                $mci->prepare('
                    INSERT INTO mci_wp_import_map (id, source_type, source_id, target_type, target_id)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE target_id=VALUES(target_id)
                ')->execute([api_uuid_v4(), 'wp_post', $sourceId, 'mci_business_group', $groupId]);
                $inserted++;
            }

            $branch = fetch_one($mci, 'SELECT id FROM mci_business_branches WHERE business_group_id=? ORDER BY created_at ASC LIMIT 1', [$groupId]);
            if (!$branch) {
                $branchId = api_uuid_v4();
                $branchSlug = next_unique_slug($mci, 'mci_business_branches', $groupSlug . '-' . strtolower($city));
                $branchInsert->execute([
                    $branchId,
                    $groupId,
                    $branchSlug,
                    $address,
                    $city,
                    $state,
                    $country,
                    $pincode,
                    is_numeric((string)$lat) ? (float)$lat : null,
                    is_numeric((string)$lng) ? (float)$lng : null,
                    $phone,
                    $website,
                    $actorId,
                ]);
            } else {
                $branchId = (string)$branch['id'];
            }

            foreach (parse_hours_payload($hoursRaw) as $h) {
                $hoursUpsert->execute([
                    api_uuid_v4(),
                    $branchId,
                    $h['day'],
                    format_time_or_null($h['opens_at']),
                    format_time_or_null($h['closes_at']),
                    format_time_or_null($h['opens_at_2']),
                    format_time_or_null($h['closes_at_2']),
                    (int)$h['is_closed'],
                    $actorId,
                ]);
            }

            if (!empty($subcategoryIds)) {
                foreach (array_keys($subcategoryIds) as $subcategoryId) {
                    $subcategoryInsert->execute([api_uuid_v4(), $groupId, (int)$subcategoryId, $actorId]);
                }
            }

            $attachmentIds = [];
            foreach (['_thumbnail_id', 'featured_image_id'] as $k) {
                if (!empty($meta[$k]) && ctype_digit((string)$meta[$k])) {
                    $attachmentIds[] = (int)$meta[$k];
                }
            }
            foreach (['gallery_ids', 'gallery_image_ids'] as $gk) {
                if (empty($meta[$gk])) {
                    continue;
                }
                foreach (explode(',', (string)$meta[$gk]) as $idPart) {
                    $idPart = trim($idPart);
                    if (ctype_digit($idPart)) {
                        $attachmentIds[] = (int)$idPart;
                    }
                }
            }
            $attachmentIds = array_values(array_unique($attachmentIds));

            $sort = 0;
            foreach ($attachmentIds as $aid) {
                $url = (string)($attachmentGuidById[$aid] ?? '');
                if ($url === '') {
                    continue;
                }
                $copied = null;
                if ($uploadsPath !== null) {
                    $copied = maybe_copy_media($url, $uploadsPath, $mediaTarget);
                }
                $filePath = $copied ?? $url;
                $imageInsert->execute([api_uuid_v4(), $groupId, $filePath, $sort, $actorId]);
                if ($sort === 0) {
                    $mci->prepare('UPDATE mci_business_groups SET logo_path=? WHERE id=?')->execute([$filePath, $groupId]);
                }
                $sort++;
            }
        } else {
            if ($existingGroupId) {
                $updated++;
            } else {
                $inserted++;
            }
        }
    }

    return ['inserted' => $inserted, 'updated' => $updated, 'skipped' => $skipped];
}

function write_report(string $runId, array $summary): void
{
    $dir = dirname(__DIR__, 2) . '/docs/import';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $path = $dir . '/wordpress-import-report.md';
    $lines = [];
    $lines[] = '# WordPress Import Report';
    $lines[] = '';
    $lines[] = '- Run ID: `' . $runId . '`';
    $lines[] = '- Generated at: `' . gmdate('c') . '`';
    $lines[] = '- Dry run: `' . (($summary['dry_run'] ?? true) ? 'yes' : 'no') . '`';
    $lines[] = '';
    foreach (['users', 'categories', 'businesses'] as $scope) {
        $s = $summary[$scope] ?? [];
        $lines[] = '## ' . ucfirst($scope);
        foreach ($s as $k => $v) {
            $lines[] = '- ' . $k . ': `' . (string)$v . '`';
        }
        $lines[] = '';
    }
    file_put_contents($path, implode(PHP_EOL, $lines));
}

try {
    $argv = $_SERVER['argv'] ?? [];
    $dryRun = !arg_flag($argv, '--apply');

    $mci = pdo_mysql(
        env_req('MCI_DB_HOST'),
        env_req('MCI_DB_NAME'),
        env_req('MCI_DB_USER'),
        env_req('MCI_DB_PASS')
    );
    $wp = pdo_mysql(
        env_req('WP_DB_HOST'),
        env_req('WP_DB_NAME'),
        env_req('WP_DB_USER'),
        env_req('WP_DB_PASS'),
        env_opt('WP_DB_PORT')
    );
    $wpPrefix = detect_wp_prefix($wp);
    putenv('WP_TABLE_PREFIX=' . $wpPrefix);
    $_ENV['WP_TABLE_PREFIX'] = $wpPrefix;
    $_SERVER['WP_TABLE_PREFIX'] = $wpPrefix;

    ensure_import_tables($mci);
    $runId = api_uuid_v4();
    $mci->prepare('INSERT INTO mci_wp_import_runs (id, is_dry_run) VALUES (?, ?)')->execute([$runId, $dryRun ? 1 : 0]);

    $listingTypes = parse_list_env('WP_LISTING_POST_TYPES', DEFAULT_LISTING_TYPES);
    $taxonomies = parse_list_env('WP_CATEGORY_TAXONOMIES', DEFAULT_CATEGORY_TAXONOMIES);

    $summary = ['dry_run' => $dryRun];
    echo "Starting user import...\n";
    $summary['users'] = import_users($wp, $mci, $runId, $dryRun);
    echo "Starting category import...\n";
    $summary['categories'] = import_categories($wp, $mci, $runId, $dryRun, $taxonomies);
    echo "Starting business import...\n";
    $summary['businesses'] = import_businesses($wp, $mci, $runId, $dryRun, $listingTypes);

    $mci->prepare('
        UPDATE mci_wp_import_runs
        SET status = ?, finished_at = NOW(6), summary_json = ?
        WHERE id = ?
    ')->execute([
        'done',
        json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        $runId,
    ]);

    write_report($runId, $summary);
    echo "Import finished. Run ID: {$runId}\n";
    echo "Mode: " . ($dryRun ? "dry-run\n" : "apply\n");
    exit(0);
} catch (Throwable $e) {
    if (isset($mci, $runId) && $mci instanceof PDO && is_string($runId) && $runId !== '') {
        try {
            $mci->prepare('UPDATE mci_wp_import_runs SET status = ?, finished_at = NOW(6), error_message = ? WHERE id = ?')
                ->execute(['failed', substr($e->getMessage(), 0, 500), $runId]);
        } catch (Throwable $inner) {
            // Ignore secondary failure while surfacing original error.
        }
    }
    fwrite(STDERR, 'Import failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

