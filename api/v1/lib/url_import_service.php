<?php
declare(strict_types=1);

require_once __DIR__ . '/uuid.php';
require_once __DIR__ . '/scraper_curl_fallback.php';

// ---------------------------------------------------------------------------
// Job CRUD
// ---------------------------------------------------------------------------

/**
 * Create a new import job and return its UUID.
 *
 * @param string $mode    'url_list' | 'crawler'
 * @param array  $config  Mode config: {urls:[...]} or {index_url, pattern, limit}
 */
function url_import_create_job(PDO $pdo, string $userId, string $mode, array $config): string
{
    $id         = api_uuid_v4();
    $configJson = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $totalUrls = match ($mode) {
        'url_list' => count($config['urls'] ?? []),
        'crawler'  => 0, // unknown until index page is crawled
        default    => 0,
    };

    $stmt = $pdo->prepare(
        'INSERT INTO mci_url_import_jobs
           (id, mode, created_by_user_id, config_json, status, total_urls)
         VALUES (?, ?, ?, ?, \'pending\', ?)'
    );
    $stmt->execute([$id, $mode, $userId, $configJson, $totalUrls]);

    return $id;
}

/**
 * Fetch a single job row with log decoded.
 */
function url_import_get_job(PDO $pdo, string $jobId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, mode, status, total_urls, processed_urls, inserted_count,
                skipped_count, log_json, error_message, config_json,
                started_at, finished_at, created_at
           FROM mci_url_import_jobs WHERE id = ?'
    );
    $stmt->execute([$jobId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    $row['log']    = json_decode((string)($row['log_json'] ?? '[]'), true) ?: [];
    $row['config'] = json_decode((string)($row['config_json'] ?? '{}'), true) ?: [];

    return $row;
}

// ---------------------------------------------------------------------------
// Fire-and-forget processor trigger
// ---------------------------------------------------------------------------

/**
 * Fire a loopback HTTP request to the processor endpoint so it runs
 * independently under its own PHP execution context. The caller returns
 * immediately (CURLOPT_TIMEOUT = 1).
 */
function url_import_trigger_processor(string $jobId, string $jwt): void
{
    // Build loopback URL using the server's own host/port
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
    $url    = $scheme . '://' . $host . '/api/v1/cp/url-import/jobs/' . urlencode($jobId) . '/process';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => '{}',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 1,     // Return immediately; processor runs on
        CURLOPT_SSL_VERIFYPEER => false, // Loopback — skip cert check
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $jwt,
        ],
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// ---------------------------------------------------------------------------
// Core processor
// ---------------------------------------------------------------------------

/**
 * Process a job: fetch each URL, extract business data, insert into staging.
 * Call this from the /process API route. Sets extended time limit.
 *
 * @return array{ok: bool, inserted: int, skipped: int, errors: int}
 */
function url_import_run_job(PDO $pdo, string $jobId, string $actorId): array
{
    @set_time_limit(300);
    @ignore_user_abort(true);

    $job = url_import_get_job($pdo, $jobId);
    if (!$job) {
        return ['ok' => false, 'inserted' => 0, 'skipped' => 0, 'errors' => 0];
    }
    if ($job['status'] === 'running' || $job['status'] === 'done') {
        return ['ok' => true, 'inserted' => 0, 'skipped' => 0, 'errors' => 0];
    }

    // Mark running
    $pdo->prepare("UPDATE mci_url_import_jobs SET status='running', started_at=NOW(6) WHERE id=?")
        ->execute([$jobId]);

    $scraper = new ScraperCurlFallback();
    $config  = $job['config'];
    $mode    = $job['mode'];

    try {
        // Resolve URL list
        if ($mode === 'crawler') {
            $indexUrl = trim((string)($config['index_url'] ?? ''));
            $pattern  = trim((string)($config['pattern']  ?? ''));
            $limit    = max(1, min(100, (int)($config['limit'] ?? 20)));

            $urls = $scraper->discoverUrls($indexUrl, $pattern, $limit);

            // Update total now that we know it
            $pdo->prepare("UPDATE mci_url_import_jobs SET total_urls=? WHERE id=?")
                ->execute([count($urls), $jobId]);
        } else {
            $urls = array_values(array_filter(
                array_map('trim', (array)($config['urls'] ?? [])),
                fn($u) => $u !== ''
            ));
        }

        $inserted = 0;
        $skipped  = 0;
        $errors   = 0;

        foreach ($urls as $url) {
            // URL-level dedup
            if (url_import_url_already_scraped($pdo, $url)) {
                $skipped++;
                url_import_append_log($pdo, $jobId, ['url' => $url, 'status' => 'skipped_dup']);
                url_import_increment_progress($pdo, $jobId, 0, 1);
                continue;
            }

            $records = $scraper->extractFromUrl($url);

            if (empty($records)) {
                $errors++;
                url_import_append_log($pdo, $jobId, ['url' => $url, 'status' => 'no_data']);
                url_import_increment_progress($pdo, $jobId, 0, 0);
                continue;
            }

            $names       = [];
            $urlInserted = 0;

            foreach ($records as $rec) {
                // Content-level dedup (source + source_id hash)
                $sourceId = md5($rec['name'] . ($rec['address'] ?? '') . ($rec['phone'] ?? ''));

                $check = $pdo->prepare(
                    'SELECT id FROM mci_scraped_businesses WHERE source = ? AND source_id = ? LIMIT 1'
                );
                $check->execute(['curl_scrape', $sourceId]);
                if ($check->fetch()) {
                    $skipped++;
                    continue;
                }

                $id = api_uuid_v4();
                $ins = $pdo->prepare(
                    'INSERT INTO mci_scraped_businesses
                       (id, source, source_id, source_url, query_params, name, category_hint,
                        types_raw, city, phone, website, address, latitude, longitude,
                        payload_json, status, scraped_by_user_id)
                     VALUES (?, \'curl_scrape\', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'pending_review\', ?)'
                );
                $ins->execute([
                    $id,
                    $sourceId,
                    $url,
                    json_encode(['url' => $url, 'job_id' => $jobId]),
                    $rec['name'],
                    $rec['category_hint'] ?? null,
                    json_encode($rec['types_raw'] ?? []),
                    $rec['city'] ?? null,
                    $rec['phone'] ?? null,
                    $rec['website'] ?? null,
                    $rec['address'] ?? null,
                    $rec['latitude'] ?? null,
                    $rec['longitude'] ?? null,
                    $rec['payload_json'] ?? '{}',
                    $actorId,
                ]);

                $names[] = $rec['name'];
                $urlInserted++;
                $inserted++;
            }

            url_import_append_log($pdo, $jobId, [
                'url'    => $url,
                'status' => $urlInserted > 0 ? 'ok' : 'skipped_dup',
                'names'  => $names,
            ]);
            url_import_increment_progress($pdo, $jobId, $urlInserted, 0);
        }

        $pdo->prepare(
            "UPDATE mci_url_import_jobs
                SET status='done', finished_at=NOW(6),
                    inserted_count=inserted_count+?,
                    skipped_count=skipped_count+?
              WHERE id=?"
        )->execute([$inserted, $skipped, $jobId]);

        return ['ok' => true, 'inserted' => $inserted, 'skipped' => $skipped, 'errors' => $errors];

    } catch (Throwable $e) {
        $pdo->prepare(
            "UPDATE mci_url_import_jobs SET status='failed', error_message=?, finished_at=NOW(6) WHERE id=?"
        )->execute([substr($e->getMessage(), 0, 500), $jobId]);

        return ['ok' => false, 'inserted' => 0, 'skipped' => 0, 'errors' => 1];
    }
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Increment processed_urls counter (and optionally inserted_count).
 */
function url_import_increment_progress(PDO $pdo, string $jobId, int $insertedDelta, int $skippedDelta): void
{
    $pdo->prepare(
        'UPDATE mci_url_import_jobs
            SET processed_urls  = processed_urls  + 1,
                inserted_count  = inserted_count  + ?,
                skipped_count   = skipped_count   + ?
          WHERE id = ?'
    )->execute([$insertedDelta, $skippedDelta, $jobId]);
}

/**
 * Append one entry to log_json (read-modify-write; log stays manageable).
 */
function url_import_append_log(PDO $pdo, string $jobId, array $entry): void
{
    $stmt = $pdo->prepare('SELECT log_json FROM mci_url_import_jobs WHERE id = ?');
    $stmt->execute([$jobId]);
    $existing = json_decode((string)($stmt->fetchColumn() ?: '[]'), true) ?: [];
    $existing[] = $entry;
    $pdo->prepare('UPDATE mci_url_import_jobs SET log_json = ? WHERE id = ?')
        ->execute([json_encode($existing, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $jobId]);
}

/**
 * Check if a URL has already been scraped (source_url dedup).
 */
function url_import_url_already_scraped(PDO $pdo, string $url): bool
{
    $stmt = $pdo->prepare('SELECT id FROM mci_scraped_businesses WHERE source_url = ? LIMIT 1');
    $stmt->execute([$url]);
    return (bool)$stmt->fetch();
}
