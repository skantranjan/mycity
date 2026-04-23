<?php

declare(strict_types=1);

/**
 * Master sitemap index + dedicated child sitemap files.
 *
 * - /sitemap.xml is always a <sitemapindex>.
 * - Child sitemap families:
 *   - common pages
 *   - businesses
 *   - business categories
 *   - products
 *   - services
 *   - locations
 *   - tags
 * - Each family auto-splits when rows exceed 50,000 URLs.
 */

require_once __DIR__ . '/../includes/mci_paths.php';
require_once __DIR__ . '/../api/v1/lib/db.php';

const MCI_SITEMAP_MAX_URLS = 50000;

/** @return list<string> */
function mci_sitemap_kinds(): array
{
    return [
        'common',
        'businesses',
        'business-categories',
        'products',
        'services',
        'locations',
        'tags',
    ];
}

/** @return list<array{0: string, 1: string, 2: string}> */
function mci_sitemap_common_pages(): array
{
    return [
        ['/', 'daily', '1.0'],
        ['/business-listing/', 'daily', '0.9'],
        ['/products/', 'daily', '0.8'],
        ['/services/', 'daily', '0.8'],
        ['/business-category/', 'weekly', '0.8'],
        ['/location/', 'weekly', '0.7'],
        ['/tag/', 'weekly', '0.6'],
        ['/about/', 'monthly', '0.6'],
        ['/contact/', 'monthly', '0.5'],
        ['/privacy-policy/', 'yearly', '0.3'],
        ['/terms-of-use/', 'yearly', '0.3'],
        ['/disclaimer/', 'yearly', '0.3'],
        ['/cookies/', 'yearly', '0.3'],
    ];
}

function mci_sitemap_city_slug(string $city): string
{
    $city = strtolower(trim($city));
    $city = preg_replace('/\s+/', '-', $city) ?? '';
    $city = preg_replace('/[^a-z0-9\-]+/', '', $city) ?? '';

    return trim($city, '-');
}

function mci_sitemap_location(string $path): string
{
    $path = $path === '' ? '/' : ($path[0] === '/' ? $path : '/' . $path);
    $prefix = mci_app_web_base_path();
    if ($prefix !== '') {
        $path = '/' . trim($prefix, '/') . $path;
    }

    return rtrim(mci_site_base_url(), '/') . $path;
}

function mci_sitemap_xml_text(string $s): string
{
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function mci_sitemap_date(?string $datetime): ?string
{
    if ($datetime === null || $datetime === '') {
        return null;
    }
    $t = strtotime($datetime);

    return $t !== false ? gmdate('Y-m-d', $t) : null;
}

function mci_sitemap_emit_url(string $loc, string $changefreq, string $priority, ?string $lastmod = null): void
{
    echo '  <url>';
    echo '<loc>', mci_sitemap_xml_text($loc), '</loc>';
    if ($lastmod !== null && $lastmod !== '') {
        echo '<lastmod>', mci_sitemap_xml_text($lastmod), '</lastmod>';
    }
    echo '<changefreq>', mci_sitemap_xml_text($changefreq), '</changefreq>';
    echo '<priority>', mci_sitemap_xml_text($priority), '</priority>';
    echo "</url>\n";
}

function mci_sitemap_begin_urlset(): void
{
    header('Content-Type: application/xml; charset=UTF-8');
    header('Cache-Control: public, max-age=1800');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
}

function mci_sitemap_end_urlset(): void
{
    echo '</urlset>';
}

function mci_sitemap_begin_index(): void
{
    header('Content-Type: application/xml; charset=UTF-8');
    header('Cache-Control: public, max-age=1800');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
}

function mci_sitemap_emit_sitemap_ref(string $path, ?string $lastmod = null): void
{
    echo '  <sitemap>';
    echo '<loc>', mci_sitemap_xml_text(mci_sitemap_location($path)), '</loc>';
    if ($lastmod !== null && $lastmod !== '') {
        echo '<lastmod>', mci_sitemap_xml_text($lastmod), '</lastmod>';
    }
    echo "</sitemap>\n";
}

function mci_sitemap_end_index(): void
{
    echo '</sitemapindex>';
}

function mci_sitemap_kind_basename(string $kind): string
{
    return match ($kind) {
        'common' => 'sitemap-common',
        'businesses' => 'sitemap-businesses',
        'business-categories' => 'sitemap-business-categories',
        'products' => 'sitemap-products',
        'services' => 'sitemap-services',
        'locations' => 'sitemap-locations',
        'tags' => 'sitemap-tags',
        default => 'sitemap-common',
    };
}

/** @return array<string, int> */
function mci_sitemap_counts(PDO $pdo): array
{
    return [
        'common' => count(mci_sitemap_common_pages()),
        'businesses' => (int)$pdo->query("
            SELECT COUNT(*) FROM mci_business_groups
            WHERE status = 'live' AND slug <> ''
        ")->fetchColumn(),
        'business-categories' => (int)$pdo->query("
            SELECT COUNT(DISTINCT c.id)
            FROM mci_categories c
            INNER JOIN mci_business_groups g ON g.parent_category_id = c.id AND g.status = 'live'
            WHERE c.parent_id IS NULL AND c.slug <> ''
        ")->fetchColumn(),
        'products' => (int)$pdo->query("
            SELECT COUNT(DISTINCT c.id)
            FROM mci_categories c
            INNER JOIN mci_business_groups g ON g.parent_category_id = c.id AND g.status = 'live'
            INNER JOIN mci_business_products p ON p.business_group_id = g.id AND p.is_active = 1
            WHERE c.parent_id IS NULL AND c.slug <> ''
        ")->fetchColumn(),
        'services' => (int)$pdo->query("
            SELECT COUNT(DISTINCT c.id)
            FROM mci_categories c
            INNER JOIN mci_business_groups g ON g.parent_category_id = c.id AND g.status = 'live'
            INNER JOIN mci_business_services s ON s.business_group_id = g.id AND s.is_active = 1
            WHERE c.parent_id IS NULL AND c.slug <> ''
        ")->fetchColumn(),
        'locations' => (int)$pdo->query("
            SELECT COUNT(*) FROM (
                SELECT DISTINCT TRIM(b.city) AS city_name
                FROM mci_business_branches b
                INNER JOIN mci_business_groups g ON g.id = b.business_group_id AND g.status = 'live'
                WHERE b.status = 'active' AND TRIM(b.city) <> ''
            ) t
        ")->fetchColumn(),
        'tags' => (int)$pdo->query("
            SELECT COUNT(DISTINCT t.id)
            FROM mci_tags t
            INNER JOIN mci_business_tags bt ON bt.tag_id = t.id
            INNER JOIN mci_business_groups g ON g.id = bt.business_group_id AND g.status = 'live'
            WHERE t.slug <> ''
        ")->fetchColumn(),
    ];
}

function mci_sitemap_lastmod(PDO $pdo, string $kind): string
{
    $sql = match ($kind) {
        'businesses' => "
            SELECT MAX(COALESCE(updated_at, created_at))
            FROM mci_business_groups
            WHERE status = 'live' AND slug <> ''
        ",
        'business-categories' => "
            SELECT MAX(c.created_at)
            FROM mci_categories c
            INNER JOIN mci_business_groups g ON g.parent_category_id = c.id AND g.status = 'live'
            WHERE c.parent_id IS NULL AND c.slug <> ''
        ",
        'products' => "
            SELECT MAX(COALESCE(p.created_at, g.updated_at, g.created_at))
            FROM mci_business_products p
            INNER JOIN mci_business_groups g ON g.id = p.business_group_id AND g.status = 'live'
            WHERE p.is_active = 1
        ",
        'services' => "
            SELECT MAX(COALESCE(s.created_at, g.updated_at, g.created_at))
            FROM mci_business_services s
            INNER JOIN mci_business_groups g ON g.id = s.business_group_id AND g.status = 'live'
            WHERE s.is_active = 1
        ",
        'locations' => "
            SELECT MAX(COALESCE(b.updated_at, b.created_at, g.updated_at, g.created_at))
            FROM mci_business_branches b
            INNER JOIN mci_business_groups g ON g.id = b.business_group_id AND g.status = 'live'
            WHERE b.status = 'active' AND TRIM(b.city) <> ''
        ",
        'tags' => "
            SELECT MAX(t.created_at)
            FROM mci_tags t
            INNER JOIN mci_business_tags bt ON bt.tag_id = t.id
            INNER JOIN mci_business_groups g ON g.id = bt.business_group_id AND g.status = 'live'
            WHERE t.slug <> ''
        ",
        default => null,
    };

    if ($sql === null) {
        return gmdate('Y-m-d');
    }
    $raw = $pdo->query($sql)?->fetchColumn();
    $d = mci_sitemap_date(is_string($raw) ? $raw : null);

    return $d ?? gmdate('Y-m-d');
}

function mci_sitemap_emit_common_slice(int $part): void
{
    $offset = ($part - 1) * MCI_SITEMAP_MAX_URLS;
    $rows = array_slice(mci_sitemap_common_pages(), $offset, MCI_SITEMAP_MAX_URLS);
    foreach ($rows as $row) {
        mci_sitemap_emit_url(mci_sitemap_location($row[0]), $row[1], $row[2]);
    }
}

function mci_sitemap_emit_db_slice(PDO $pdo, string $kind, int $part): void
{
    $offset = ($part - 1) * MCI_SITEMAP_MAX_URLS;
    $lastmodFmt = static fn(array $r): ?string => mci_sitemap_date((string)($r['ts'] ?? ''));

    $cfg = match ($kind) {
        'businesses' => [
            'sql' => "
                SELECT slug, COALESCE(updated_at, created_at) AS ts
                FROM mci_business_groups
                WHERE status = 'live' AND slug <> ''
                ORDER BY slug
                LIMIT :lim OFFSET :off
            ",
            'freq' => 'weekly',
            'pri' => '0.85',
            'loc' => static fn(array $r): string => mci_sitemap_location('/business/' . rawurlencode((string)$r['slug']) . '/'),
        ],
        'business-categories' => [
            'sql' => "
                SELECT c.slug, MAX(c.created_at) AS ts
                FROM mci_categories c
                INNER JOIN mci_business_groups g ON g.parent_category_id = c.id AND g.status = 'live'
                WHERE c.parent_id IS NULL AND c.slug <> ''
                GROUP BY c.id, c.slug
                ORDER BY c.slug
                LIMIT :lim OFFSET :off
            ",
            'freq' => 'weekly',
            'pri' => '0.75',
            'loc' => static fn(array $r): string => mci_sitemap_location('/business-category/' . rawurlencode((string)$r['slug']) . '/'),
        ],
        'products' => [
            'sql' => "
                SELECT c.slug, MAX(COALESCE(p.created_at, g.updated_at, g.created_at)) AS ts
                FROM mci_categories c
                INNER JOIN mci_business_groups g ON g.parent_category_id = c.id AND g.status = 'live'
                INNER JOIN mci_business_products p ON p.business_group_id = g.id AND p.is_active = 1
                WHERE c.parent_id IS NULL AND c.slug <> ''
                GROUP BY c.id, c.slug
                ORDER BY c.slug
                LIMIT :lim OFFSET :off
            ",
            'freq' => 'weekly',
            'pri' => '0.70',
            'loc' => static fn(array $r): string => mci_sitemap_location('/products/' . rawurlencode((string)$r['slug']) . '/'),
        ],
        'services' => [
            'sql' => "
                SELECT c.slug, MAX(COALESCE(s.created_at, g.updated_at, g.created_at)) AS ts
                FROM mci_categories c
                INNER JOIN mci_business_groups g ON g.parent_category_id = c.id AND g.status = 'live'
                INNER JOIN mci_business_services s ON s.business_group_id = g.id AND s.is_active = 1
                WHERE c.parent_id IS NULL AND c.slug <> ''
                GROUP BY c.id, c.slug
                ORDER BY c.slug
                LIMIT :lim OFFSET :off
            ",
            'freq' => 'weekly',
            'pri' => '0.70',
            'loc' => static fn(array $r): string => mci_sitemap_location('/services/' . rawurlencode((string)$r['slug']) . '/'),
        ],
        'locations' => [
            'sql' => "
                SELECT TRIM(b.city) AS city_name,
                       MAX(COALESCE(b.updated_at, b.created_at, g.updated_at, g.created_at)) AS ts
                FROM mci_business_branches b
                INNER JOIN mci_business_groups g ON g.id = b.business_group_id AND g.status = 'live'
                WHERE b.status = 'active' AND TRIM(b.city) <> ''
                GROUP BY TRIM(b.city)
                ORDER BY TRIM(b.city)
                LIMIT :lim OFFSET :off
            ",
            'freq' => 'weekly',
            'pri' => '0.72',
            'loc' => static function (array $r): string {
                $slug = mci_sitemap_city_slug((string)($r['city_name'] ?? ''));
                return mci_sitemap_location('/location/' . rawurlencode($slug) . '/');
            },
        ],
        'tags' => [
            'sql' => "
                SELECT t.slug, MAX(t.created_at) AS ts
                FROM mci_tags t
                INNER JOIN mci_business_tags bt ON bt.tag_id = t.id
                INNER JOIN mci_business_groups g ON g.id = bt.business_group_id AND g.status = 'live'
                WHERE t.slug <> ''
                GROUP BY t.id, t.slug
                ORDER BY t.slug
                LIMIT :lim OFFSET :off
            ",
            'freq' => 'weekly',
            'pri' => '0.65',
            'loc' => static fn(array $r): string => mci_sitemap_location('/tag/' . rawurlencode((string)$r['slug']) . '/'),
        ],
        default => null,
    };

    if (!is_array($cfg)) {
        return;
    }

    $stmt = $pdo->prepare($cfg['sql']);
    $stmt->bindValue(':lim', MCI_SITEMAP_MAX_URLS, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();

    while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
        $loc = $cfg['loc']($row);
        if ($loc === '') {
            continue;
        }
        mci_sitemap_emit_url($loc, $cfg['freq'], $cfg['pri'], $lastmodFmt($row));
    }
}

// Request routing.
$kind = isset($_GET['kind']) ? trim((string)$_GET['kind']) : '';
$part = isset($_GET['part']) ? (int)$_GET['part'] : 1;
if ($part < 1) {
    $part = 1;
}

$pdo = null;
$counts = ['common' => count(mci_sitemap_common_pages())];
try {
    $pdo = api_db();
    $counts = mci_sitemap_counts($pdo);
} catch (Throwable $ignored) {
}

if ($kind !== '') {
    if (!in_array($kind, mci_sitemap_kinds(), true)) {
        http_response_code(404);
        exit;
    }
    if ($kind !== 'common' && !$pdo instanceof PDO) {
        http_response_code(503);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Sitemap temporarily unavailable.';
        exit;
    }

    $count = (int)($counts[$kind] ?? 0);
    $parts = $count > 0 ? (int)ceil($count / MCI_SITEMAP_MAX_URLS) : 1;
    if ($part > $parts) {
        http_response_code(404);
        exit;
    }

    mci_sitemap_begin_urlset();
    if ($kind === 'common') {
        mci_sitemap_emit_common_slice($part);
    } else {
        mci_sitemap_emit_db_slice($pdo, $kind, $part);
    }
    mci_sitemap_end_urlset();
    exit;
}

// Main master sitemap index (/sitemap.xml).
mci_sitemap_begin_index();

$today = gmdate('Y-m-d');
foreach (mci_sitemap_kinds() as $k) {
    $count = (int)($counts[$k] ?? 0);
    if ($k !== 'common' && !$pdo instanceof PDO) {
        continue;
    }

    $parts = $count > 0 ? (int)ceil($count / MCI_SITEMAP_MAX_URLS) : 1;
    $base = mci_sitemap_kind_basename($k);
    $lastmod = $k === 'common' || !$pdo instanceof PDO
        ? $today
        : mci_sitemap_lastmod($pdo, $k);

    for ($i = 1; $i <= $parts; $i++) {
        $suffix = $parts > 1 ? '-' . $i : '';
        mci_sitemap_emit_sitemap_ref('/' . $base . $suffix . '.xml', $lastmod);
    }
}

mci_sitemap_end_index();
