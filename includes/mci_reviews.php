<?php

declare(strict_types=1);

/**
 * Business reviews — DB-backed with full edit history.
 *
 * Each user can have exactly one active review per business (one row in
 * mci_business_reviews). Every add or update also appends a row to
 * mci_business_review_history so the full audit trail is preserved.
 *
 * Public display shows "Anonymous" unless the caller resolves user names.
 */

require_once __DIR__ . '/../api/v1/lib/db.php';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function mci_reviews_uuid(): string
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// ---------------------------------------------------------------------------
// Read helpers
// ---------------------------------------------------------------------------

/**
 * Load all reviews for a business group id, newest first.
 *
 * @return list<array{id:string,business_group_id:string,user_id:string,rating:int,text:string,created_at:string,updated_at:string,submitted_by:string}>
 */
function mci_reviews_for_group(string $businessGroupId): array
{
    if ($businessGroupId === '') {
        return [];
    }
    try {
        $pdo  = api_db();
        $stmt = $pdo->prepare(
            "SELECT id, business_group_id, user_id, rating, review_text AS text,
                    created_at, updated_at, user_id AS submitted_by
             FROM mci_business_reviews
             WHERE business_group_id = ?
             ORDER BY updated_at DESC"
        );
        $stmt->execute([$businessGroupId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['rating'] = (int)$r['rating'];
        }
        unset($r);
        return $rows;
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Load the current review for a specific user + business, or null if none.
 */
function mci_reviews_user_review(string $businessGroupId, string $userId): ?array
{
    if ($businessGroupId === '' || $userId === '') {
        return null;
    }
    try {
        $pdo  = api_db();
        $stmt = $pdo->prepare(
            "SELECT id, rating, review_text AS text, created_at, updated_at
             FROM mci_business_reviews
             WHERE business_group_id = ? AND user_id = ?
             LIMIT 1"
        );
        $stmt->execute([$businessGroupId, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $row['rating'] = (int)$row['rating'];
        return $row;
    } catch (Throwable $e) {
        return null;
    }
}

function mci_reviews_user_has_reviewed_group(string $businessGroupId, string $userId): bool
{
    return mci_reviews_user_review($businessGroupId, $userId) !== null;
}

// ---------------------------------------------------------------------------
// Write helpers
// ---------------------------------------------------------------------------

/**
 * Add a new review. Returns ['ok' => true] or ['ok' => false, 'error' => '…'].
 */
function mci_reviews_add(string $businessGroupId, int $rating, string $text, string $userId): array
{
    if ($businessGroupId === '') {
        return ['ok' => false, 'error' => 'Invalid listing.'];
    }
    if ($userId === '') {
        return ['ok' => false, 'error' => 'You must be signed in to submit a review.'];
    }
    if ($rating < 1 || $rating > 5) {
        return ['ok' => false, 'error' => 'Please select a star rating from 1 to 5.'];
    }
    $text = trim($text);
    $len  = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
    if ($len < 10) {
        return ['ok' => false, 'error' => 'Please write at least 10 characters in your review.'];
    }
    if ($len > 2000) {
        return ['ok' => false, 'error' => 'Review is too long (maximum 2000 characters).'];
    }
    if (mci_reviews_user_has_reviewed_group($businessGroupId, $userId)) {
        return ['ok' => false, 'error' => 'You have already reviewed this business. Use the edit option to update it.'];
    }

    try {
        $pdo       = api_db();
        $reviewId  = mci_reviews_uuid();
        $historyId = mci_reviews_uuid();
        $now       = gmdate('Y-m-d H:i:s');

        $pdo->prepare(
            "INSERT INTO mci_business_reviews
                (id, business_group_id, user_id, rating, review_text, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        )->execute([$reviewId, $businessGroupId, $userId, $rating, $text, $now, $now]);

        $pdo->prepare(
            "INSERT INTO mci_business_review_history
                (id, review_id, business_group_id, user_id, rating, review_text, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        )->execute([$historyId, $reviewId, $businessGroupId, $userId, $rating, $text, $now]);

        return ['ok' => true];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Could not save your review. Please try again.'];
    }
}

/**
 * Update an existing review. Appends a history row for the old + new values.
 * Returns ['ok' => true] or ['ok' => false, 'error' => '…'].
 */
function mci_reviews_update(string $businessGroupId, int $rating, string $text, string $userId): array
{
    if ($businessGroupId === '' || $userId === '') {
        return ['ok' => false, 'error' => 'Invalid request.'];
    }
    if ($rating < 1 || $rating > 5) {
        return ['ok' => false, 'error' => 'Please select a star rating from 1 to 5.'];
    }
    $text = trim($text);
    $len  = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
    if ($len < 10) {
        return ['ok' => false, 'error' => 'Please write at least 10 characters in your review.'];
    }
    if ($len > 2000) {
        return ['ok' => false, 'error' => 'Review is too long (maximum 2000 characters).'];
    }

    $existing = mci_reviews_user_review($businessGroupId, $userId);
    if ($existing === null) {
        return ['ok' => false, 'error' => 'No existing review found to update.'];
    }

    try {
        $pdo       = api_db();
        $historyId = mci_reviews_uuid();
        $now       = gmdate('Y-m-d H:i:s');

        $pdo->prepare(
            "UPDATE mci_business_reviews
             SET rating = ?, review_text = ?, updated_at = ?
             WHERE id = ?"
        )->execute([$rating, $text, $now, $existing['id']]);

        $pdo->prepare(
            "INSERT INTO mci_business_review_history
                (id, review_id, business_group_id, user_id, rating, review_text, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        )->execute([$historyId, $existing['id'], $businessGroupId, $userId, $rating, $text, $now]);

        return ['ok' => true];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Could not update your review. Please try again.'];
    }
}

// ---------------------------------------------------------------------------
// Display helpers (unchanged public API)
// ---------------------------------------------------------------------------

/** Filled / empty stars for display (Bootstrap Icons). */
function mci_reviews_stars_html(int $rating): string
{
    $rating = max(0, min(5, $rating));
    $out    = '<span class="mci-reviews-stars" aria-hidden="true">';
    for ($i = 1; $i <= 5; $i++) {
        $out .= $i <= $rating
            ? '<i class="bi bi-star-fill mci-reviews-star--on"></i>'
            : '<i class="bi bi-star mci-reviews-star--off"></i>';
    }
    $out .= '</span>';
    return $out;
}

/**
 * @param list<array{rating: int}> $reviews
 * @return array{average: float, count: int}
 */
function mci_reviews_summary(array $reviews): array
{
    $n   = 0;
    $sum = 0;
    foreach ($reviews as $r) {
        if (!is_array($r) || !isset($r['rating'])) {
            continue;
        }
        $star = (int)$r['rating'];
        if ($star >= 1 && $star <= 5) {
            $sum += $star;
            $n++;
        }
    }
    if ($n === 0) {
        return ['average' => 0.0, 'count' => 0];
    }
    return ['average' => round($sum / $n, 1), 'count' => $n];
}

// ---------------------------------------------------------------------------
// Legacy slug-based wrappers (used by business/index.php)
// These look up the business_group_id from the slug before delegating.
// ---------------------------------------------------------------------------

function mci_reviews_group_id_for_slug(string $slug): string
{
    if ($slug === '') {
        return '';
    }
    try {
        $pdo  = api_db();
        $stmt = $pdo->prepare("SELECT id FROM mci_business_groups WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (string)$row['id'] : '';
    } catch (Throwable $e) {
        return '';
    }
}

/**
 * Returns merged reviews for a business slug (DB rows + demo rows).
 * Sorted newest first. Each row has: id, rating, text, created_at, submitted_by.
 */
function mci_reviews_merged_for_display(string $slug): array
{
    $groupId = mci_reviews_group_id_for_slug($slug);
    $dbRows  = $groupId !== '' ? mci_reviews_for_group($groupId) : [];
    $demos   = mci_reviews_demo_for_slug($slug);

    $merged = array_merge($demos, $dbRows);
    usort($merged, static function (array $a, array $b) {
        $ta = strtotime($a['updated_at'] ?? $a['created_at'] ?? '0') ?: 0;
        $tb = strtotime($b['updated_at'] ?? $b['created_at'] ?? '0') ?: 0;
        return $tb <=> $ta;
    });
    return $merged;
}

function mci_reviews_user_has_reviewed(string $slug, string $userId): bool
{
    $groupId = mci_reviews_group_id_for_slug($slug);
    return $groupId !== '' && mci_reviews_user_has_reviewed_group($groupId, $userId);
}

/** Built-in demo reviews (shown even when DB is empty). */
function mci_reviews_demo_for_slug(string $slug): array
{
    $demos = [
        'property-852' => [
            [
                'id'           => 'demo-property-1',
                'rating'       => 5,
                'text'         => 'Very professional team and smooth viewing process. They answered every question about the lease and the building amenities.',
                'created_at'   => '2026-01-14T11:20:00+00:00',
                'updated_at'   => '2026-01-14T11:20:00+00:00',
                'submitted_by' => '',
            ],
            [
                'id'           => 'demo-property-2',
                'rating'       => 4,
                'text'         => 'Good selection of units. Response time on email could be a bit faster, but overall happy with the experience.',
                'created_at'   => '2026-02-02T09:45:00+00:00',
                'updated_at'   => '2026-02-02T09:45:00+00:00',
                'submitted_by' => '',
            ],
        ],
        'famous-veg-restaurant-bhopal' => [
            [
                'id'           => 'demo-rest-1',
                'rating'       => 5,
                'text'         => 'Thali was excellent and the staff were friendly. Great value for a weekend lunch with family.',
                'created_at'   => '2026-01-28T13:10:00+00:00',
                'updated_at'   => '2026-01-28T13:10:00+00:00',
                'submitted_by' => '',
            ],
        ],
        'jxf-painting' => [
            [
                'id'           => 'demo-paint-1',
                'rating'       => 5,
                'text'         => 'Clean job, on time, and they protected our floors properly. Would recommend for condo repaints.',
                'created_at'   => '2026-02-05T16:00:00+00:00',
                'updated_at'   => '2026-02-05T16:00:00+00:00',
                'submitted_by' => '',
            ],
        ],
    ];
    return $demos[$slug] ?? [];
}
