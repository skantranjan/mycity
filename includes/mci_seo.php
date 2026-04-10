<?php

declare(strict_types=1);

/**
 * Produce a document <title> in the 50–60 character range when possible (SEO).
 * Long titles are truncated on a word boundary; short titles get a site suffix.
 */
function mci_seo_document_title(?string $pageTitle): string
{
    $min = 50;
    $max = 60;
    $default = 'My City Info - India free local business directory';

    $t = trim((string)($pageTitle ?? ''));
    if ($t === '') {
        $t = $default;
    }

    $len = mb_strlen($t, 'UTF-8');
    if ($len > $max) {
        return mci_seo_truncate_title($t, $max);
    }
    if ($len >= $min) {
        return $t;
    }

    $separator = ' | ';
    $suffixes = [
        "India's free local business directory",
        'Free local business directory India',
        'Local business directory India',
        'Business directory India',
        'Directory India',
        'India directory',
        'India',
    ];

    $bestUnderMax = null;
    $bestLen = 0;

    foreach ($suffixes as $suffix) {
        $candidate = $t . $separator . $suffix;
        $cLen = mb_strlen($candidate, 'UTF-8');
        if ($cLen > $max) {
            continue;
        }
        if ($cLen >= $min) {
            return $candidate;
        }
        if ($cLen > $bestLen) {
            $bestLen = $cLen;
            $bestUnderMax = $candidate;
        }
    }

    if ($bestUnderMax !== null) {
        return $bestUnderMax;
    }

    return mci_seo_truncate_title($t . $separator . $suffixes[0], $max);
}

function mci_seo_truncate_title(string $title, int $maxLen): string
{
    if (mb_strlen($title, 'UTF-8') <= $maxLen) {
        return $title;
    }

    $ellipsis = '…';
    $ellipsisLen = mb_strlen($ellipsis, 'UTF-8');
    $budget = $maxLen - $ellipsisLen;
    if ($budget < 1) {
        return mb_substr($title, 0, $maxLen, 'UTF-8');
    }

    $chunk = mb_substr($title, 0, $budget, 'UTF-8');
    $lastSpace = mb_strrpos($chunk, ' ', 0, 'UTF-8');
    if ($lastSpace !== false && $lastSpace > (int)($budget * 0.45)) {
        $chunk = mb_substr($chunk, 0, $lastSpace, 'UTF-8');
    }

    return rtrim($chunk) . $ellipsis;
}

/** Safe JSON for <script type="application/ld+json">. */
function mci_schema_json_ld_encode(array $data): string
{
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
}

/**
 * Sitewide Schema.org graph: Organization + WebSite with SearchAction.
 * Business pages add LocalBusiness via $extraHead; multiple graphs are valid.
 */
function mci_schema_default_site_graph(): array
{
    $origin = rtrim(mci_site_base_url(), '/');
    $home = rtrim($origin . mci_web_path('/'), '/') . '/';
    $logo = $origin . mci_web_path('/assets/images/logo.png');
    $searchTemplate = $origin . mci_web_path('/business-listing/') . '?what={search_term_string}';

    $organization = [
        '@type' => 'Organization',
        '@id' => $home . '#organization',
        'name' => 'My City Info',
        'url' => $home,
        'logo' => [
            '@type' => 'ImageObject',
            'url' => $logo,
        ],
        'description' => 'Free online directory of local businesses, services and places across India.',
    ];

    if (defined('MCI_FOUNDING_YEAR') && (int) MCI_FOUNDING_YEAR > 0) {
        $organization['foundingDate'] = (string) (int) MCI_FOUNDING_YEAR;
    }

    return [
        '@context' => 'https://schema.org',
        '@graph' => [
            $organization,
            [
                '@type' => 'WebSite',
                '@id' => $home . '#website',
                'url' => $home,
                'name' => 'My City Info',
                'description' => 'Discover and list local businesses by category, city and search.',
                'publisher' => ['@id' => $home . '#organization'],
                'inLanguage' => 'en',
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => [
                        '@type' => 'EntryPoint',
                        'urlTemplate' => $searchTemplate,
                    ],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ],
    ];
}

/**
 * Self-referencing canonical + OG URL for /business-listing/ (allowed query keys only, sorted).
 */
function mci_seo_listings_public_url(): string
{
    $origin = rtrim(mci_site_base_url(), '/');
    $path = mci_web_path('/business-listing/');
    $keys = ['what', 'where', 'category', 'subcategory', 'tag', 'price_range', 'page'];
    $pairs = [];
    foreach ($keys as $k) {
        $v = isset($_GET[$k]) ? trim((string) $_GET[$k]) : '';
        if ($v === '') {
            continue;
        }
        if ($k === 'page' && (int) $v < 2) {
            continue;
        }
        $pairs[$k] = $v;
    }
    if ($pairs === []) {
        return $origin . $path;
    }
    ksort($pairs, SORT_STRING);

    return $origin . $path . '?' . http_build_query($pairs, '', '&', PHP_QUERY_RFC3986);
}
