<?php

declare(strict_types=1);

/**
 * IANA timezone helpers for profile `mci_userprofiles.timezone`.
 * Store identifiers like "Asia/Kolkata" — use with PHP DateTimeZone / DateTime for correct DST math.
 */

/**
 * True if empty (cleared) or a valid IANA zone id from PHP's bundled tzdata.
 */
function mci_timezone_is_valid(?string $ianaId): bool
{
    if ($ianaId === null || $ianaId === '') {
        return true;
    }
    $ianaId = trim($ianaId);
    if ($ianaId === '') {
        return true;
    }
    try {
        new DateTimeZone($ianaId);
    } catch (Throwable $e) {
        return false;
    }

    return in_array($ianaId, DateTimeZone::listIdentifiers(), true);
}

/**
 * Human label: "(GMT+05:30) India Standard Time – Kolkata"
 * Uses Intl when available for the middle segment; city = last path segment.
 */
function mci_timezone_display_label(string $ianaId): string
{
    $dtz = new DateTimeZone($ianaId);
    $dt = new DateTime('now', $dtz);
    $p = $dt->format('P'); // +05:30
    $gmt = '(GMT' . $p . ')';

    $parts = explode('/', $ianaId);
    $city = count($parts) >= 2 ? str_replace('_', ' ', (string) end($parts)) : $ianaId;

    $middle = '';
    if (extension_loaded('intl') && class_exists(IntlTimeZone::class)) {
        try {
            $intlTz = IntlTimeZone::createTimeZone($ianaId);
            $middle = (string) $intlTz->getDisplayName(false, IntlTimeZone::DISPLAY_LONG);
        } catch (Throwable $e) {
            $middle = '';
        }
    }
    if ($middle === '') {
        $middle = $city;
    }

    return trim($gmt . ' ' . $middle . ' – ' . $city);
}

/**
 * @return array<int, array{id: string, label: string, region: string, offset_minutes: int}>
 */
function mci_timezone_list_options(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $utc = new DateTimeZone('UTC');
    $now = new DateTime('now', $utc);
    $out = [];

    foreach (DateTimeZone::listIdentifiers() as $id) {
        try {
            $z = new DateTimeZone($id);
        } catch (Throwable $e) {
            continue;
        }
        $offsetSec = $z->getOffset($now);
        $region = explode('/', $id, 2)[0] ?? 'Other';
        $out[] = [
            'id' => $id,
            'label' => mci_timezone_display_label($id),
            'region' => $region,
            'offset_minutes' => (int) round($offsetSec / 60),
        ];
    }

    usort(
        $out,
        static function (array $a, array $b): int {
            $c = $a['offset_minutes'] <=> $b['offset_minutes'];
            if ($c !== 0) {
                return $c;
            }

            return strcmp($a['label'], $b['label']);
        }
    );

    $cache = $out;

    return $cache;
}

/**
 * @return array<string, list<array{id: string, label: string}>>
 */
function mci_timezone_options_grouped_by_region(): array
{
    $groups = [];
    foreach (mci_timezone_list_options() as $row) {
        $r = $row['region'];
        if (!isset($groups[$r])) {
            $groups[$r] = [];
        }
        $groups[$r][] = ['id' => $row['id'], 'label' => $row['label']];
    }
    ksort($groups, SORT_NATURAL);

    return $groups;
}
