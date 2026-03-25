<?php
declare(strict_types=1);

require_once __DIR__ . '/uuid.php';
require_once __DIR__ . '/business_helpers.php';
require_once __DIR__ . '/scraper_adapter_interface.php';
require_once __DIR__ . '/scraper_osm.php';
require_once __DIR__ . '/scraper_tomtom.php';
require_once __DIR__ . '/scraper_here.php';
require_once __DIR__ . '/scraper_google_places.php';
require_once __DIR__ . '/scraper_foursquare.php';
require_once __DIR__ . '/scraper_curl_fallback.php';

// ---------------------------------------------------------------------------
// Adapter registry
// ---------------------------------------------------------------------------

/**
 * Returns all registered adapters (all six sources).
 * @return ScraperAdapter[]
 */
function scraper_adapters(): array
{
    static $instances = null;
    if ($instances === null) {
        $instances = [
            new ScraperOsm(),
            new ScraperTomTom(),
            new ScraperHere(),
            new ScraperGooglePlaces(),
            new ScraperFoursquare(),
            new ScraperCurlFallback(),
        ];
    }
    return $instances;
}

/**
 * Returns a single adapter by sourceName, or null if not found.
 */
function scraper_adapter_by_name(string $name): ?ScraperAdapter
{
    foreach (scraper_adapters() as $adapter) {
        if ($adapter->sourceName() === $name) {
            return $adapter;
        }
    }
    return null;
}

// ---------------------------------------------------------------------------
// Usage tracking
// ---------------------------------------------------------------------------

/**
 * Increment call/result counters for a source in the current month.
 */
function scraper_usage_increment(PDO $pdo, string $source, int $calls, int $results): void
{
    $pdo->prepare('
        INSERT INTO mci_scraper_usage (source, `year_month`, call_count, results_count)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          call_count     = call_count     + VALUES(call_count),
          results_count  = results_count  + VALUES(results_count)
    ')->execute([$source, date('Y-m'), $calls, $results]);
}

/**
 * Get usage row for a source and month (defaults to current month).
 * Returns ['source', '`year_month`', 'call_count', 'results_count'] or empty array.
 */
function scraper_usage_get(PDO $pdo, string $source, ?string $yearMonth = null): array
{
    $ym   = $yearMonth ?? date('Y-m');
    $stmt = $pdo->prepare('
        SELECT source, `year_month`, call_count, results_count
        FROM   mci_scraper_usage
        WHERE  source = ? AND `year_month` = ?
    ');
    $stmt->execute([$source, $ym]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Get usage for all sources for a given month (defaults to current month).
 * Returns array keyed by source name.
 */
function scraper_usage_all(PDO $pdo, ?string $yearMonth = null): array
{
    $ym   = $yearMonth ?? date('Y-m');
    $stmt = $pdo->prepare('
        SELECT source, `year_month`, call_count, results_count
        FROM   mci_scraper_usage
        WHERE  `year_month` = ?
    ');
    $stmt->execute([$ym]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $bySource = [];
    foreach ($rows as $row) {
        $bySource[$row['source']] = $row;
    }
    return $bySource;
}

/**
 * Returns true when call_count has reached or exceeded the monthly limit.
 * Always returns false when $limit is null (unlimited sources like OSM).
 */
function scraper_usage_is_at_limit(PDO $pdo, string $source, ?int $limit): bool
{
    if ($limit === null) {
        return false;
    }
    $usage = scraper_usage_get($pdo, $source);
    $count = (int)($usage['call_count'] ?? 0);
    return $count >= $limit;
}

/**
 * Returns true when call_count >= (limit * threshold / 100) — i.e. near the limit.
 * Returns false when $limit is null (unlimited).
 */
function scraper_usage_is_near_limit(PDO $pdo, string $source, ?int $limit, int $threshold): bool
{
    if ($limit === null) {
        return false;
    }
    $usage = scraper_usage_get($pdo, $source);
    $count = (int)($usage['call_count'] ?? 0);
    return $count >= (int)($limit * $threshold / 100);
}

// ---------------------------------------------------------------------------
// Search — main entry point
// ---------------------------------------------------------------------------

/**
 * Run a scrape search using the specified source (or 'auto' for best available).
 *
 * @param  array  $params           ['q', 'city', 'category', 'source']
 * @param  string $scraperUserId    CP admin user ID performing the scrape
 * @return array  {
 *   ok:           bool,
 *   error?:       string,
 *   source_used:  string,
 *   inserted:     int,
 *   skipped_dups: int,
 *   results:      array   // inserted records (preview rows)
 * }
 */
function scraper_search(PDO $pdo, array $params, string $scraperUserId): array
{
    $sourceName = trim((string)($params['source'] ?? 'auto'));

    // ── Resolve adapter ────────────────────────────────────────────────────
    if ($sourceName === 'auto') {
        $adapter = null;
        foreach (scraper_adapters() as $a) {
            if ($a->isAvailable()) {
                $adapter = $a;
                break;
            }
        }
        if ($adapter === null) {
            return ['ok' => false, 'error' => 'no_adapters_available'];
        }
    } else {
        $adapter = scraper_adapter_by_name($sourceName);
        if ($adapter === null) {
            return ['ok' => false, 'error' => 'unknown_source'];
        }
        if (!$adapter->isAvailable()) {
            return ['ok' => false, 'error' => 'source_not_configured'];
        }
    }

    $usedSource = $adapter->sourceName();

    // ── Hard limit check ───────────────────────────────────────────────────
    if (scraper_usage_is_at_limit($pdo, $usedSource, $adapter->monthlyLimit())) {
        return ['ok' => false, 'error' => 'monthly_limit_reached', 'source_used' => $usedSource];
    }

    // ── Call external API ──────────────────────────────────────────────────
    $records = $adapter->search($params);

    // ── Track usage ────────────────────────────────────────────────────────
    scraper_usage_increment($pdo, $usedSource, 1, count($records));

    // ── Dedup + insert ─────────────────────────────────────────────────────
    $queryParamsJson = json_encode($params, JSON_UNESCAPED_SLASHES);
    $inserted        = 0;
    $skipped         = 0;
    $insertedRows    = [];

    $checkStmt = $pdo->prepare('
        SELECT id FROM mci_scraped_businesses
        WHERE  source = ? AND source_id = ?
        LIMIT  1
    ');

    $insertStmt = $pdo->prepare('
        INSERT INTO mci_scraped_businesses
          (id, source, source_id, source_url, query_params,
           name, category_hint, types_raw, city, phone, website,
           address, latitude, longitude, payload_json,
           status, scraped_by_user_id)
        VALUES
          (?, ?, ?, ?, ?,
           ?, ?, ?, ?, ?, ?,
           ?, ?, ?, ?,
           \'pending_review\', ?)
    ');

    foreach ($records as $rec) {
        $checkStmt->execute([$usedSource, $rec['source_id']]);
        if ($checkStmt->fetch()) {
            $skipped++;
            continue;
        }

        $id = api_uuid_v4();
        $insertStmt->execute([
            $id,
            $usedSource,
            $rec['source_id'],
            $rec['source_url']   ?? null,
            $queryParamsJson,
            $rec['name'],
            $rec['category_hint'] ?? null,
            !empty($rec['types_raw']) ? json_encode($rec['types_raw']) : null,
            $rec['city']          ?? null,
            $rec['phone']         ?? null,
            $rec['website']       ?? null,
            $rec['address']       ?? null,
            $rec['latitude']      ?? null,
            $rec['longitude']     ?? null,
            $rec['payload_json'],
            $scraperUserId,
        ]);

        $inserted++;
        $insertedRows[] = [
            'id'            => $id,
            'name'          => $rec['name'],
            'city'          => $rec['city']          ?? null,
            'phone'         => $rec['phone']         ?? null,
            'website'       => $rec['website']       ?? null,
            'address'       => $rec['address']       ?? null,
            'category_hint' => $rec['category_hint'] ?? null,
            'has_hours'     => !empty(json_decode($rec['payload_json'], true)['hours'] ?? []),
        ];
    }

    return [
        'ok'          => true,
        'source_used' => $usedSource,
        'inserted'    => $inserted,
        'skipped_dups'=> $skipped,
        'results'     => $insertedRows,
    ];
}

// ---------------------------------------------------------------------------
// List / Get
// ---------------------------------------------------------------------------

/**
 * List scraped businesses with optional filters.
 *
 * @param  array $filters  ['status', 'source', 'city', 'q', 'page', 'per_page']
 */
function scraper_list(PDO $pdo, array $filters = []): array
{
    $status  = $filters['status'] ?? null;
    $source  = $filters['source'] ?? null;
    $city    = $filters['city']   ?? null;
    $q       = trim((string)($filters['q'] ?? ''));
    $page    = max(1, (int)($filters['page'] ?? 1));
    $perPage = min(100, max(10, (int)($filters['per_page'] ?? 25)));
    $offset  = ($page - 1) * $perPage;

    $where  = [];
    $binds  = [];

    if ($status !== null && $status !== '') {
        $where[] = 'status = ?';
        $binds[] = $status;
    }
    if ($source !== null && $source !== '') {
        $where[] = 'source = ?';
        $binds[] = $source;
    }
    if ($city !== null && $city !== '') {
        $where[] = 'city LIKE ?';
        $binds[] = '%' . $city . '%';
    }
    if ($q !== '') {
        $where[] = '(name LIKE ? OR address LIKE ? OR category_hint LIKE ?)';
        $binds[] = '%' . $q . '%';
        $binds[] = '%' . $q . '%';
        $binds[] = '%' . $q . '%';
    }

    $whereClause = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM mci_scraped_businesses $whereClause");
    $countStmt->execute($binds);
    $total = (int)$countStmt->fetchColumn();

    $listStmt = $pdo->prepare("
        SELECT id, source, source_id, source_url, name, category_hint, types_raw,
               city, phone, website, address, latitude, longitude,
               status, rejection_reason, scraped_by_user_id, created_at
        FROM   mci_scraped_businesses
        $whereClause
        ORDER  BY created_at DESC
        LIMIT  ? OFFSET ?
    ");
    $listStmt->execute(array_merge($binds, [$perPage, $offset]));
    $rows = $listStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['types_raw'] = $row['types_raw'] !== null ? json_decode($row['types_raw'], true) : [];
    }
    unset($row);

    return [
        'total'    => $total,
        'page'     => $page,
        'per_page' => $perPage,
        'items'    => $rows,
    ];
}

/**
 * Get a single scraped business record by ID.
 */
function scraper_get(PDO $pdo, string $id): ?array
{
    $stmt = $pdo->prepare('
        SELECT * FROM mci_scraped_businesses WHERE id = ? LIMIT 1
    ');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }
    $row['types_raw']    = $row['types_raw']    !== null ? json_decode($row['types_raw'], true)    : [];
    $row['payload_json'] = $row['payload_json'] !== null ? json_decode($row['payload_json'], true) : [];
    return $row;
}

// ---------------------------------------------------------------------------
// Update payload (admin edits before import)
// ---------------------------------------------------------------------------

/**
 * Update the payload_json of a pending scraped record.
 * Also refreshes the denormalised display columns.
 */
function scraper_update_payload(PDO $pdo, string $id, array $payload, string $actorId): bool
{
    $group  = $payload['group']  ?? [];
    $branch = $payload['branch'] ?? [];

    $stmt = $pdo->prepare('
        UPDATE mci_scraped_businesses
        SET    payload_json = ?,
               name         = ?,
               city         = ?,
               phone        = ?,
               website      = ?,
               address      = ?
        WHERE  id = ? AND status = \'pending_review\'
    ');

    $name    = trim((string)($group['name'] ?? ''));
    $city    = trim((string)($branch['city'] ?? ''));
    $phone   = trim((string)($branch['phone_primary'] ?? ''));
    $website = trim((string)($group['website_url'] ?? ''));
    $addrParts = array_filter([
        $branch['address_line1'] ?? null,
        $branch['city'] ?? null,
        $branch['state'] ?? null,
        $branch['pincode'] ?? null,
        'India',
    ]);
    $address = implode(', ', $addrParts);

    $stmt->execute([
        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        $name   !== '' ? $name   : null,
        $city   !== '' ? $city   : null,
        $phone  !== '' ? $phone  : null,
        $website !== '' ? $website : null,
        $address !== '' ? $address : null,
        $id,
    ]);

    return $stmt->rowCount() > 0;
}

// ---------------------------------------------------------------------------
// Approve / Import
// ---------------------------------------------------------------------------

/**
 * Import a scraped record into the main business tables, then delete the staging row.
 *
 * Does NOT use api_business_create() because that function has a schema mismatch
 * (pre-existing bug: uses wrong column names for branch and hours tables).
 * Instead, this function inserts directly using the actual migration 008 column names.
 *
 * @param  string $status  'live' | 'draft' (default 'live')
 * @return array  {ok:bool, group_id?:string, branch_id?:string, error?:string}
 */
function scraper_approve(PDO $pdo, string $id, string $actorId, string $importStatus = 'live'): array
{
    $row = scraper_get($pdo, $id);
    if ($row === null) {
        return ['ok' => false, 'error' => 'not_found'];
    }
    if ($row['status'] !== 'pending_review') {
        return ['ok' => false, 'error' => 'already_processed'];
    }

    $payload = is_array($row['payload_json']) ? $row['payload_json'] : json_decode((string)$row['payload_json'], true);
    if (!is_array($payload)) {
        return ['ok' => false, 'error' => 'invalid_payload'];
    }

    $group        = $payload['group']  ?? [];
    $branch       = $payload['branch'] ?? [];
    $hours        = $payload['hours']  ?? [];
    $socialLinks  = $payload['social_links'] ?? [];
    $dataSource   = (string)($payload['data_source'] ?? ('scrape_' . $row['source']));

    // ── Validate required fields ───────────────────────────────────────────
    $name       = trim((string)($group['name'] ?? ''));
    $categoryId = (int)($group['parent_category_id'] ?? 0);
    $branchCity = trim((string)($branch['city'] ?? ''));
    $addrLine1  = trim((string)($branch['address_line1'] ?? ''));

    if ($name === '') {
        return ['ok' => false, 'error' => 'name_required'];
    }
    if ($categoryId <= 0) {
        return ['ok' => false, 'error' => 'category_required'];
    }
    if ($branchCity === '' && $addrLine1 === '') {
        return ['ok' => false, 'error' => 'address_or_city_required'];
    }

    $groupId  = api_uuid_v4();
    $branchId = api_uuid_v4();

    $groupSlug  = api_business_group_next_unique_slug($pdo, $name);
    $branchSlug = api_business_branch_next_unique_slug($pdo, $groupSlug, $branchCity ?: 'branch');

    $pdo->beginTransaction();
    try {
        // ── mci_business_groups ───────────────────────────────────────────
        $pdo->prepare('
            INSERT INTO mci_business_groups
              (id, name, slug, tagline, description, established_year,
               website_url, email, parent_category_id, price_range,
               page_title, meta_keywords, meta_description,
               status, added_by_role, added_by_user_id,
               created_by_user_id, data_source)
            VALUES
              (?, ?, ?, ?, ?, ?,
               ?, ?, ?, ?,
               ?, ?, ?,
               ?, \'cp_admin\', ?,
               ?, ?)
        ')->execute([
            $groupId,
            $name,
            $groupSlug,
            trim((string)($group['tagline'] ?? ''))      ?: null,
            trim((string)($group['description'] ?? ''))  ?: null,
            !empty($group['established_year']) ? (int)$group['established_year'] : null,
            trim((string)($group['website_url'] ?? ''))  ?: null,
            trim((string)($group['email'] ?? ''))        ?: null,
            $categoryId,
            in_array($group['price_range'] ?? '', ['free', 'moderate', 'pricey', 'ultra'], true) ? $group['price_range'] : null,
            trim((string)($group['page_title'] ?? ''))   ?: $name,
            trim((string)($group['meta_keywords'] ?? '')) ?: null,
            trim((string)($group['meta_description'] ?? '')) ?: null,
            $importStatus,
            $actorId,
            $actorId,
            $dataSource,
        ]);

        // ── mci_business_tags ─────────────────────────────────────────────
        $tagIds = array_filter(array_map('intval', (array)($group['tag_ids'] ?? [])));
        if (!empty($tagIds)) {
            $tagStmt = $pdo->prepare('
                INSERT IGNORE INTO mci_business_tags (id, business_group_id, tag_id, created_by_user_id)
                VALUES (?, ?, ?, ?)
            ');
            foreach ($tagIds as $tId) {
                if ($tId > 0) {
                    $tagStmt->execute([api_uuid_v4(), $groupId, $tId, $actorId]);
                }
            }
        }

        // ── mci_business_branches ─────────────────────────────────────────
        $pdo->prepare('
            INSERT INTO mci_business_branches
              (id, business_group_id, slug,
               address_line1, address_line2, city, state, country, pincode,
               latitude, longitude,
               phone_primary, phone_secondary, whatsapp_number,
               status, created_by_user_id)
            VALUES
              (?, ?, ?,
               ?, ?, ?, ?, ?, ?,
               ?, ?,
               ?, ?, ?,
               \'active\', ?)
        ')->execute([
            $branchId,
            $groupId,
            $branchSlug,
            $addrLine1    !== '' ? $addrLine1 : null,
            trim((string)($branch['address_line2'] ?? '')) ?: null,
            $branchCity   !== '' ? $branchCity : null,
            trim((string)($branch['state']   ?? '')) ?: null,
            trim((string)($branch['country'] ?? 'India')) ?: 'India',
            trim((string)($branch['pincode'] ?? '')) ?: null,
            is_numeric($branch['latitude']  ?? '')  ? (float)$branch['latitude']  : null,
            is_numeric($branch['longitude'] ?? '')  ? (float)$branch['longitude'] : null,
            trim((string)($branch['phone_primary']   ?? '')) ?: null,
            trim((string)($branch['phone_secondary'] ?? '')) ?: null,
            trim((string)($branch['whatsapp_number'] ?? '')) ?: null,
            $actorId,
        ]);

        // ── mci_business_branch_hours ─────────────────────────────────────
        if (!empty($hours)) {
            $hourStmt = $pdo->prepare('
                INSERT INTO mci_business_branch_hours
                  (id, branch_id, day_of_week, opens_at, closes_at, is_closed, created_by_user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                  opens_at=VALUES(opens_at), closes_at=VALUES(closes_at), is_closed=VALUES(is_closed)
            ');
            $validDays = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
            foreach ($hours as $h) {
                $day = strtolower(trim((string)($h['day_of_week'] ?? '')));
                if (!in_array($day, $validDays, true)) {
                    continue;
                }
                $isClosed = !empty($h['is_closed']) ? 1 : 0;
                $hourStmt->execute([
                    api_uuid_v4(),
                    $branchId,
                    $day,
                    $isClosed ? null : (trim((string)($h['opens_at']  ?? '')) ?: null),
                    $isClosed ? null : (trim((string)($h['closes_at'] ?? '')) ?: null),
                    $isClosed,
                    $actorId,
                ]);
            }
        }

        // ── mci_business_social_links ─────────────────────────────────────
        $validPlatforms = ['facebook','instagram','twitter','youtube','linkedin',
                           'tiktok','pinterest','threads','snapchat','whatsapp_channel','telegram','other'];
        if (!empty($socialLinks)) {
            $slStmt = $pdo->prepare('
                INSERT IGNORE INTO mci_business_social_links
                  (id, business_group_id, platform, url, label, sort_order, created_by_user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            foreach ($socialLinks as $i => $sl) {
                $platform = strtolower(trim((string)($sl['platform'] ?? '')));
                $url      = trim((string)($sl['url'] ?? ''));
                if (!in_array($platform, $validPlatforms, true) || $url === '') {
                    continue;
                }
                $slStmt->execute([
                    api_uuid_v4(), $groupId, $platform, $url,
                    trim((string)($sl['label'] ?? '')) ?: null,
                    $i,
                    $actorId,
                ]);
            }
        }

        // ── Mark staging row before deletion ──────────────────────────────
        $pdo->prepare('
            UPDATE mci_scraped_businesses
            SET    status             = \'imported\',
                   imported_group_id  = ?,
                   imported_at        = NOW(6),
                   reviewed_by_user_id= ?,
                   reviewed_at        = NOW(6)
            WHERE  id = ?
        ')->execute([$groupId, $actorId, $id]);

        // ── Delete staging row ─────────────────────────────────────────────
        $pdo->prepare('DELETE FROM mci_scraped_businesses WHERE id = ?')->execute([$id]);

        $pdo->commit();

        return ['ok' => true, 'group_id' => $groupId, 'branch_id' => $branchId];

    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['ok' => false, 'error' => 'db_error', 'detail' => $e->getMessage()];
    }
}

// ---------------------------------------------------------------------------
// Reject
// ---------------------------------------------------------------------------

/**
 * Mark a scraped record as rejected with an optional reason.
 */
function scraper_reject(PDO $pdo, string $id, string $actorId, ?string $reason): bool
{
    $stmt = $pdo->prepare('
        UPDATE mci_scraped_businesses
        SET    status              = \'rejected\',
               rejection_reason   = ?,
               reviewed_by_user_id= ?,
               reviewed_at        = NOW(6)
        WHERE  id = ? AND status = \'pending_review\'
    ');
    $stmt->execute([$reason, $actorId, $id]);
    return $stmt->rowCount() > 0;
}

// ---------------------------------------------------------------------------
// Counts (for sidebar badge and dashboard)
// ---------------------------------------------------------------------------

/**
 * Returns counts grouped by status.
 */
function scraper_counts(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT status, COUNT(*) AS cnt
        FROM   mci_scraped_businesses
        GROUP  BY status
    ');
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $counts = ['pending_review' => 0, 'imported' => 0, 'rejected' => 0, 'total' => 0];
    foreach ($rows as $r) {
        $counts[$r['status']] = (int)$r['cnt'];
    }
    $counts['total'] = array_sum([$counts['pending_review'], $counts['imported'], $counts['rejected']]);
    return $counts;
}

// ---------------------------------------------------------------------------
// Tag hint matching
// ---------------------------------------------------------------------------

/**
 * Match raw type strings from a scraper source against mci_tags.
 * Returns array of {id, name, slug} for tags whose name or slug fuzzy-matches
 * any of the given type hints.
 *
 * @param  string[] $types  e.g. ['restaurant', 'fast_food', 'cafe']
 * @return array[]
 */
function scraper_tag_hints(PDO $pdo, array $types): array
{
    if (empty($types)) {
        return [];
    }

    // Normalise type strings: lower, replace underscores/dashes with space
    $normalised = array_unique(array_filter(array_map(static function (string $t): string {
        return strtolower(trim(str_replace(['_', '-'], ' ', $t)));
    }, $types)));

    if (empty($normalised)) {
        return [];
    }

    // Build OR LIKE clauses
    $placeholders = implode(' OR ', array_fill(0, count($normalised), 'LOWER(name) LIKE ? OR LOWER(slug) LIKE ?'));
    $binds        = [];
    foreach ($normalised as $n) {
        $binds[] = '%' . $n . '%';
        $binds[] = '%' . $n . '%';
    }

    $stmt = $pdo->prepare("
        SELECT id, name, slug
        FROM   mci_tags
        WHERE  ($placeholders)
        ORDER  BY name
        LIMIT  20
    ");
    $stmt->execute($binds);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ---------------------------------------------------------------------------
// Usage status for dashboard display
// ---------------------------------------------------------------------------

/**
 * Returns enriched status for all adapters combined with current-month usage.
 * Used by the scraper dashboard UI.
 */
function scraper_adapter_status(PDO $pdo): array
{
    $usageBySource = scraper_usage_all($pdo);
    $result        = [];

    foreach (scraper_adapters() as $adapter) {
        $source    = $adapter->sourceName();
        $limit     = $adapter->monthlyLimit();
        $threshold = $adapter->alertThreshold();
        $available = $adapter->isAvailable();

        $usage      = $usageBySource[$source] ?? ['call_count' => 0, 'results_count' => 0];
        $callCount  = (int)($usage['call_count'] ?? 0);
        $resCount   = (int)($usage['results_count'] ?? 0);

        $atLimit   = $available && scraper_usage_is_at_limit($pdo, $source, $limit);
        $nearLimit = $available && !$atLimit && scraper_usage_is_near_limit($pdo, $source, $limit, $threshold);

        $pct = ($limit !== null && $limit > 0) ? round($callCount / $limit * 100, 1) : null;

        $result[] = [
            'source'        => $source,
            'available'     => $available,
            'monthly_limit' => $limit,
            'threshold'     => $threshold,
            'call_count'    => $callCount,
            'results_count' => $resCount,
            'usage_pct'     => $pct,
            'at_limit'      => $atLimit,
            'near_limit'    => $nearLimit,
        ];
    }
    return $result;
}
