<?php

declare(strict_types=1);

/**
 * Anonymous public reviews: identity (submitted_by) is stored server-side only
 * and never exposed in HTML. Public sees "Anonymous" + date + stars + text.
 */

function mci_reviews_storage_path(): string
{
    return __DIR__ . '/../storage/business_reviews.json';
}

function mci_reviews_load_all(): array
{
    $path = mci_reviews_storage_path();
    if (!is_readable($path)) {
        return [];
    }
    $raw = file_get_contents($path);
    $data = json_decode($raw !== false && $raw !== '' ? $raw : '{}', true);
    return is_array($data) ? $data : [];
}

function mci_reviews_save_all(array $data): bool
{
    $path = mci_reviews_storage_path();
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return false;
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }
    return file_put_contents($path, $json) !== false;
}

function mci_reviews_for_slug(string $slug): array
{
    $all = mci_reviews_load_all();
    $list = $all[$slug] ?? [];
    return is_array($list) ? $list : [];
}

function mci_reviews_user_has_reviewed(string $slug, string $userId): bool
{
    if ($userId === '') {
        return false;
    }
    foreach (mci_reviews_for_slug($slug) as $r) {
        if (!is_array($r)) {
            continue;
        }
        if (($r['submitted_by'] ?? '') === $userId) {
            return true;
        }
    }
    return false;
}

/**
 * @return array{ok: bool, error?: string}
 */
function mci_reviews_add(string $slug, int $rating, string $text, string $userId): array
{
    if ($slug === '') {
        return ['ok' => false, 'error' => 'Invalid listing.'];
    }
    if ($userId === '') {
        return ['ok' => false, 'error' => 'You must be signed in to submit a review.'];
    }
    if ($rating < 1 || $rating > 5) {
        return ['ok' => false, 'error' => 'Please select a star rating from 1 to 5.'];
    }
    $text = trim($text);
    $len = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
    if ($len < 10) {
        return ['ok' => false, 'error' => 'Please write at least 10 characters in your review.'];
    }
    if ($len > 2000) {
        return ['ok' => false, 'error' => 'Review is too long (maximum 2000 characters).'];
    }
    if (mci_reviews_user_has_reviewed($slug, $userId)) {
        return ['ok' => false, 'error' => 'You have already submitted a review for this business.'];
    }

    $all = mci_reviews_load_all();
    if (!isset($all[$slug]) || !is_array($all[$slug])) {
        $all[$slug] = [];
    }
    $all[$slug][] = [
        'id' => bin2hex(random_bytes(8)),
        'rating' => $rating,
        'text' => $text,
        'created_at' => gmdate('c'),
        'submitted_by' => $userId,
    ];
    if (!mci_reviews_save_all($all)) {
        return ['ok' => false, 'error' => 'Could not save your review. Ensure the storage/ folder is writable.'];
    }
    return ['ok' => true];
}

/** Built-in demo reviews (no real user id); shown merged with saved reviews. */
function mci_reviews_demo_for_slug(string $slug): array
{
    $demos = [
        'property-852' => [
            [
                'id' => 'demo-property-1',
                'rating' => 5,
                'text' => 'Very professional team and smooth viewing process. They answered every question about the lease and the building amenities.',
                'created_at' => '2026-01-14T11:20:00+00:00',
            ],
            [
                'id' => 'demo-property-2',
                'rating' => 4,
                'text' => 'Good selection of units. Response time on email could be a bit faster, but overall happy with the experience.',
                'created_at' => '2026-02-02T09:45:00+00:00',
            ],
        ],
        'famous-veg-restaurant-bhopal' => [
            [
                'id' => 'demo-rest-1',
                'rating' => 5,
                'text' => 'Thali was excellent and the staff were friendly. Great value for a weekend lunch with family.',
                'created_at' => '2026-01-28T13:10:00+00:00',
            ],
        ],
        'jxf-painting' => [
            [
                'id' => 'demo-paint-1',
                'rating' => 5,
                'text' => 'Clean job, on time, and they protected our floors properly. Would recommend for condo repaints.',
                'created_at' => '2026-02-05T16:00:00+00:00',
            ],
        ],
    ];
    return $demos[$slug] ?? [];
}

/**
 * @return list<array{id: string, rating: int, text: string, created_at: string}>
 */
function mci_reviews_merged_for_display(string $slug): array
{
    $merged = array_merge(mci_reviews_demo_for_slug($slug), mci_reviews_for_slug($slug));
    usort($merged, static function ($a, $b) {
        $ta = strtotime($a['created_at'] ?? '0') ?: 0;
        $tb = strtotime($b['created_at'] ?? '0') ?: 0;
        return $tb <=> $ta;
    });
    return $merged;
}

/**
 * @param list<array{rating: int}> $reviews
 * @return array{average: float, count: int}
 */
/** Filled / empty stars for display (Bootstrap Icons). */
function mci_reviews_stars_html(int $rating): string
{
    $rating = max(0, min(5, $rating));
    $out = '<span class="mci-reviews-stars" aria-hidden="true">';
    for ($i = 1; $i <= 5; $i++) {
        $out .= $i <= $rating
            ? '<i class="bi bi-star-fill mci-reviews-star--on"></i>'
            : '<i class="bi bi-star mci-reviews-star--off"></i>';
    }
    $out .= '</span>';
    return $out;
}

function mci_reviews_summary(array $reviews): array
{
    $n = 0;
    $sum = 0;
    foreach ($reviews as $r) {
        if (!is_array($r) || !isset($r['rating'])) {
            continue;
        }
        $star = (int) $r['rating'];
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
