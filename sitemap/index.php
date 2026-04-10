<?php

declare(strict_types=1);

/**
 * Dynamic XML sitemap with automatic splitting at 50,000 URLs per file (sitemaps.org).
 *
 * - Total URLs ≤ limit: /sitemap.xml is a single <urlset>.
 * - Total URLs > limit: /sitemap.xml is a <sitemapindex> pointing at
 *   /sitemap-pages-{n}.xml and /sitemap-businesses-{n}.xml as needed.
 */

require_once __DIR__ . '/../includes/mci_paths.php';
require_once __DIR__ . '/../api/v1/lib/db.php';

const MCI_SITEMAP_MAX_URLS = 50000;

/** @return list<array{0: string, 1: string, 2: string}> */
function mci_sitemap_static_pages(): array
{
    return [
        ['/', 'daily', '1.0'],
        ['/business-listing/', 'daily', '0.9'],
        ['/business-category/', 'weekly', '0.8'],
        ['/products/', 'daily', '0.8'],
        ['/services/', 'daily', '0.8'],
        ['/tag/', 'weekly', '0.6'],
        ['/about/', 'monthly', '0.6'],
        ['/contact/', 'monthly', '0.5'],
        ['/privacy-policy/', 'yearly', '0.3'],
        ['/terms-of-use/', 'yearly', '0.3'],
        ['/disclaimer/', 'yearly', '0.3'],
        ['/cookies/', 'yearly', '0.3'],
    ];
}

/** City name → URL slug for /location/{slug}/ (matches location/index.php de-slugify). */
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

function mci_sitemap_date(?string $datetime): ?string
{
    if ($datetime === null || $datetime === '') {
        return null;
    }
    $t = strtotime($datetime);

    return $t !== false ? gmdate('Y-m-d', $t) : null;
}

/**
 * @return array{np: int, nb: int, cat: int, tag: int, prod: int, svc: int, loc: int}
 */
function mci_sitemap_counts(PDO $pdo): array
{
    $staticN = count(mci_sitemap_static_pages());

    $cat = (int)$pdo->query("
        SELECT COUNT(DISTINCT c.id)
        FROM mci_categories c
        INNER JOIN mci_business_groups g ON g.parent_category_id = c.id AND g.status = 'live'
        WHERE c.parent_id IS NULL AND c.slug <> ''
    ")->fetchColumn();

    $tag = (int)$pdo->query("
        SELECT COUNT(DISTINCT t.id)
        FROM mci_tags t
        INNER JOIN mci_business_tags bt ON bt.tag_id = t.id
        INNER JOIN mci_business_groups g ON g.id = bt.business_group_id AND g.status = 'live'
        WHERE t.slug <> ''
    ")->fetchColumn();

    $prod = (int)$pdo->query("
        SELECT COUNT(DISTINCT c.id)
        FROM mci_categories c
        INNER JOIN mci_business_groups g ON g.parent_category_id = c.id AND g.status = 'live'
        INNER JOIN mci_business_products p ON p.business_group_id = g.id AND p.is_active = 1
        WHERE c.parent_id IS NULL AND c.slug <> ''
    ")->fetchColumn();

    $svc = (int)$pdo->query("
        SELECT COUNT(DISTINCT c.id)
        FROM mci_categories c
        INNER JOIN mci_business_groups g ON g.parent_category_id = c.id AND g.status = 'live'
        INNER JOIN mci_business_services s ON s.business_group_id = g.id AND s.is_active = 1
        WHERE c.parent_id IS NULL AND c.slug <> ''
    ")->fetchColumn();

    $nb = (int)$pdo->query("
        SELECT COUNT(*) FROM mci_business_groups
        WHERE status = 'live' AND slug <> ''
    ")->fetchColumn();

    $loc = (int)$pdo->query("
        SELECT COUNT(*) FROM (
            SELECT DISTINCT TRIM(b.city) AS c
            FROM mci_business_branches b
            INNER JOIN mci_business_groups g ON g.id = b.business_group_id AND g.status = 'live'
            WHERE b.status = 'active' AND TRIM(b.city) <> ''
        ) t
    ")->fetchColumn();

    $np = $staticN + $cat + $tag + $prod + $svc + $loc;

    return ['np' => $np, 'nb' => $nb, 'cat' => $cat, 'tag' => $tag, 'prod' => $prod, 'svc' => $svc, 'loc' => $loc];
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

/**
 * Skip $skip URLs then emit up to $limit from static + DB buckets (non-business only).
 *
 * @return int Number of URLs emitted
 */
function mci_sitemap_emit_pages_slice(PDO $pdo, int $skip, int $limit): int
{
    $emitted = 0;
    if ($limit <= 0) {
        return 0;
    }

    foreach (mci_sitemap_static_pages() as $row) {
        if ($skip > 0) {
            $skip--;
            continue;
        }
        mci_sitemap_emit_url(mci_sitemap_location($row[0]), $row[1], $row[2]);
        $emitted++;
        if ($emitted >= $limit) {
            return $emitted;
        }
    }

    $buckets = [
        [
            'sql' => "
                SELECT c.slug, MAX(c.created_at) AS ts
                FROM mci_categories c
                INNER JOIN mci_business_groups g ON g.parent_category_id = c.id AND g.status = 'live'
                WHERE c.parent_id IS NULL AND c.slug <> ''
                GROUP BY c.id, c.slug
                ORDER BY c.slug
            ",
            'path' => static fn(string $slug): string => '/business-category/' . rawurlencode($slug) . '/',
            'freq' => 'weekly',
            'pri'  => '0.75',
        ],
        [
            'sql' => "
                SELECT t.slug, MAX(t.created_at) AS ts
                FROM mci_tags t
                INNER JOIN mci_business_tags bt ON bt.tag_id = t.id
                INNER JOIN mci_business_groups g ON g.id = bt.business_group_id AND g.status = 'live'
                WHERE t.slug <> ''
                GROUP BY t.id, t.slug
                ORDER BY t.slug
            ",
            'path' => static fn(string $slug): string => '/tag/' . rawurlencode($slug) . '/',
            'freq' => 'weekly',
            'pri'  => '0.65',
        ],
        [
            'sql' => "
                SELECT TRIM(b.city) AS city_name,
                       MAX(COALESCE(b.updated_at, b.created_at, g.updated_at, g.created_at)) AS ts
                FROM mci_business_branches b
                INNER JOIN mci_business_groups g ON g.id = b.business_group_id AND g.status = 'live'
                WHERE b.status = 'active' AND TRIM(b.city) <> ''
                GROUP BY TRIM(b.city)
                ORDER BY TRIM(b.city)
            ",
            'path_from_row' => true,
            'path' => static function (array $r): string {
                $city = trim((string)($r['city_name'] ?? ''));
                $slug = mci_sitemap_city_slug($city);

                return $slug !== '' ? '/location/' . rawurlencode($slug) . '/' : '';
            },
            'freq' => 'weekly',
            'pri'  => '0.72',
        ],
        [
            'sql' => "
                SELECT c.slug, MAX(c.created_at) AS ts
                FROM mci_categories c
                INNER JOIN mci_business_groups g ON g.parent_category_id = c.id AND g.status = 'live'
                INNER JOIN mci_business_products p ON p.business_group_id = g.id AND p.is_active = 1
                WHERE c.parent_id IS NULL AND c.slug <> ''
                GROUP BY c.id, c.slug
                ORDER BY c.slug
            ",
            'path' => static fn(string $slug): string => '/products/' . rawurlencode($slug) . '/',
            'freq' => 'weekly',
            'pri'  => '0.7',
        ],
        [
            'sql' => "
                SELECT c.slug, MAX(c.created_at) AS ts
                FROM mci_categories c
                INNER JOIN mci_business_groups g ON g.parent_category_id = c.id AND g.status = 'live'
                INNER JOIN mci_business_services s ON s.business_group_id = g.id AND s.is_active = 1
                WHERE c.parent_id IS NULL AND c.slug <> ''
                GROUP BY c.id, c.slug
                ORDER BY c.slug
            ",
            'path' => static fn(string $slug): string => '/services/' . rawurlencode($slug) . '/',
            'freq' => 'weekly',
            'pri'  => '0.7',
        ],
    ];

    foreach ($buckets as $bucket) {
        $stmt = $pdo->query($bucket['sql']);
        if (!$stmt) {
            continue;
        }
        while ($emitted < $limit) {
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($r === false) {
                break;
            }
            $slug = (string)($r['slug'] ?? '');
            if ($slug === '' && empty($bucket['path_from_row'])) {
                continue;
            }
            if ($skip > 0) {
                $skip--;
                continue;
            }
            $pathFn = $bucket['path'];
            if (!empty($bucket['path_from_row'])) {
                $relPath = $pathFn($r);
                if ($relPath === '') {
                    continue;
                }
                $absLoc = mci_sitemap_location($relPath);
            } else {
                $absLoc = mci_sitemap_location($pathFn($slug));
            }
            mci_sitemap_emit_url(
                $absLoc,
                $bucket['freq'],
                $bucket['pri'],
                mci_sitemap_date((string)($r['ts'] ?? ''))
            );
            $emitted++;
        }
        if ($emitted >= $limit) {
            break;
        }
    }

    return $emitted;
}

function mci_sitemap_emit_all_in_one(PDO $pdo): void
{
    mci_sitemap_begin_urlset();
    mci_sitemap_emit_pages_slice($pdo, 0, MCI_SITEMAP_MAX_URLS);

    $bizStmt = $pdo->query("
        SELECT slug,
               COALESCE(updated_at, created_at) AS ts
        FROM mci_business_groups
        WHERE status = 'live' AND slug <> ''
        ORDER BY slug
    ");
    if ($bizStmt) {
        while ($r = $bizStmt->fetch(PDO::FETCH_ASSOC)) {
            $slug = (string)($r['slug'] ?? '');
            if ($slug === '') {
                continue;
            }
            mci_sitemap_emit_url(
                mci_sitemap_location('/business/' . rawurlencode($slug) . '/'),
                'weekly',
                '0.85',
                mci_sitemap_date((string)($r['ts'] ?? ''))
            );
        }
    }
    mci_sitemap_end_urlset();
}

function mci_sitemap_emit_businesses_part(PDO $pdo, int $part): void
{
    $offset = ($part - 1) * MCI_SITEMAP_MAX_URLS;
    $stmt = $pdo->prepare("
        SELECT slug,
               COALESCE(updated_at, created_at) AS ts
        FROM mci_business_groups
        WHERE status = 'live' AND slug <> ''
        ORDER BY slug
        LIMIT :lim OFFSET :off
    ");
    $stmt->bindValue(':lim', MCI_SITEMAP_MAX_URLS, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $first = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($first === false) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');

        return;
    }

    mci_sitemap_begin_urlset();
    for ($r = $first; $r !== false; $r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $slug = (string)($r['slug'] ?? '');
        if ($slug === '') {
            continue;
        }
        mci_sitemap_emit_url(
            mci_sitemap_location('/business/' . rawurlencode($slug) . '/'),
            'weekly',
            '0.85',
            mci_sitemap_date((string)($r['ts'] ?? ''))
        );
    }
    mci_sitemap_end_urlset();
}

function mci_sitemap_max_business_ts(PDO $pdo): ?string
{
    $raw = $pdo->query("
        SELECT MAX(COALESCE(updated_at, created_at)) FROM mci_business_groups
        WHERE status = 'live' AND slug <> ''
    ")->fetchColumn();
    $d = mci_sitemap_date(is_string($raw) ? $raw : null);

    return $d ?? gmdate('Y-m-d');
}

// ── Request routing (Apache sets GET kind/part; dev server may use same) ──────
$kind = isset($_GET['kind']) ? (string)$_GET['kind'] : '';
$part = isset($_GET['part']) ? (int)$_GET['part'] : 0;

$staticOnlyNp = count(mci_sitemap_static_pages());
$counts = ['np' => $staticOnlyNp, 'nb' => 0, 'cat' => 0, 'tag' => 0, 'prod' => 0, 'svc' => 0, 'loc' => 0];
$pdo = null;
try {
    $pdo = api_db();
    $counts = mci_sitemap_counts($pdo);
} catch (Throwable $ignored) {
}

$np = (int)$counts['np'];
$nb = (int)$counts['nb'];
$nt = $np + $nb;
$needsSplit = $nt > MCI_SITEMAP_MAX_URLS;
$numPageParts = $np > 0 ? (int)max(1, (int)ceil($np / MCI_SITEMAP_MAX_URLS)) : 1;
$numBizParts = $nb > 0 ? (int)ceil($nb / MCI_SITEMAP_MAX_URLS) : 0;

// Child sitemap requests
if ($kind === 'pages' && $part >= 1) {
    if (!$pdo instanceof PDO) {
        http_response_code(503);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Sitemap temporarily unavailable.';
        exit;
    }
    if (!$needsSplit) {
        header('Location: ' . mci_sitemap_location('/sitemap.xml'), true, 302);
        exit;
    }
    if ($part > $numPageParts) {
        http_response_code(404);
        exit;
    }
    $skip = ($part - 1) * MCI_SITEMAP_MAX_URLS;
    mci_sitemap_begin_urlset();
    mci_sitemap_emit_pages_slice($pdo, $skip, MCI_SITEMAP_MAX_URLS);
    mci_sitemap_end_urlset();
    exit;
}

if ($kind === 'businesses' && $part >= 1) {
    if (!$pdo instanceof PDO) {
        http_response_code(503);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Sitemap temporarily unavailable.';
        exit;
    }
    if (!$needsSplit || $numBizParts < 1) {
        header('Location: ' . mci_sitemap_location('/sitemap.xml'), true, 302);
        exit;
    }
    if ($part > $numBizParts) {
        http_response_code(404);
        exit;
    }
    mci_sitemap_emit_businesses_part($pdo, $part);
    exit;
}

// Main /sitemap.xml
if (!$pdo instanceof PDO) {
    mci_sitemap_begin_urlset();
    foreach (mci_sitemap_static_pages() as $row) {
        mci_sitemap_emit_url(mci_sitemap_location($row[0]), $row[1], $row[2]);
    }
    mci_sitemap_end_urlset();
    exit;
}

if (!$needsSplit) {
    mci_sitemap_emit_all_in_one($pdo);
    exit;
}

// Sitemap index
$today = gmdate('Y-m-d');
$bizLastMod = $numBizParts > 0 ? mci_sitemap_max_business_ts($pdo) : null;

mci_sitemap_begin_index();
for ($p = 1; $p <= $numPageParts; $p++) {
    mci_sitemap_emit_sitemap_ref('/sitemap-pages-' . $p . '.xml', $today);
}
for ($b = 1; $b <= $numBizParts; $b++) {
    mci_sitemap_emit_sitemap_ref('/sitemap-businesses-' . $b . '.xml', $bizLastMod);
}
mci_sitemap_end_index();
