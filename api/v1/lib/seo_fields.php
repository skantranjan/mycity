<?php

declare(strict_types=1);

/**
 * Parse optional SEO fields from JSON body (categories, tags, etc.).
 *
 * @return array{0: ?string, 1: ?string, 2: ?string} page_title, meta_keywords, meta_description
 */
function api_parse_seo_fields(array $d): array
{
    $pt = isset($d['page_title']) ? trim((string) $d['page_title']) : '';
    $mk = isset($d['meta_keywords']) ? trim((string) $d['meta_keywords']) : '';
    $md = isset($d['meta_description']) ? trim((string) $d['meta_description']) : '';
    $pt = $pt === '' ? null : mb_substr($pt, 0, 255);
    $mk = $mk === '' ? null : mb_substr($mk, 0, 512);
    $md = $md === '' ? null : mb_substr($md, 0, 512);

    return [$pt, $mk, $md];
}

/**
 * Optional long-form description for categories, subcategories, or tags (editorial / UI text).
 * Distinct from meta_description (SEO snippet). Empty string clears to NULL.
 *
 * @return string|null
 */
function api_parse_description_field(array $d): ?string
{
    if (!array_key_exists('description', $d)) {
        return null;
    }
    $s = trim((string) $d['description']);

    return $s === '' ? null : mb_substr($s, 0, 65535);
}

/**
 * For PATCH-style updates: if `description` is omitted from the body, first element is false
 * (caller should not change the DB column).
 *
 * @return array{0: bool, 1: ?string}
 */
function api_parse_description_field_ex(array $d): array
{
    if (!array_key_exists('description', $d)) {
        return [false, null];
    }
    $s = trim((string) $d['description']);

    return [true, $s === '' ? null : mb_substr($s, 0, 65535)];
}
