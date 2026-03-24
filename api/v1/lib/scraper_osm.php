<?php
declare(strict_types=1);

require_once __DIR__ . '/scraper_adapter_interface.php';

/**
 * OpenStreetMap Overpass API adapter.
 *
 * Completely free, no API key, no registration required.
 * Enabled by default via MCI_SCRAPER_OSM_ENABLED=true in .env.
 *
 * Returns rich data for Indian cities including phone, website, email,
 * opening hours, social links, and established year from OSM community tags.
 */
class ScraperOsm implements ScraperAdapter
{
    public function sourceName(): string
    {
        return 'osm';
    }

    public function isAvailable(): bool
    {
        try {
            return api_env_flag('MCI_SCRAPER_OSM_ENABLED');
        } catch (Throwable) {
            return false;
        }
    }

    public function monthlyLimit(): ?int
    {
        return null; // Unlimited (fair use)
    }

    public function alertThreshold(): int
    {
        return 100; // Never alert (unlimited)
    }

    public function search(array $params): array
    {
        $q        = trim((string)($params['q'] ?? ''));
        $city     = trim((string)($params['city'] ?? ''));
        $category = trim((string)($params['category'] ?? ''));

        if ($q === '' && $city === '' && $category === '') {
            return [];
        }

        $endpoint = $this->endpoint();
        $query    = $this->buildQuery($q, $city, $category);

        $raw = $this->httpPost($endpoint, ['data' => $query]);
        if ($raw === null) {
            return [];
        }

        $json = json_decode($raw, true);
        if (!is_array($json) || !isset($json['elements'])) {
            return [];
        }

        $results = [];
        foreach ($json['elements'] as $el) {
            $record = $this->normalise($el, $q, $city, $category);
            if ($record !== null) {
                $results[] = $record;
            }
        }

        return $results;
    }

    // ── private helpers ──────────────────────────────────────────────────────

    private function endpoint(): string
    {
        try {
            $ep = api_env('MCI_SCRAPER_OSM_ENDPOINT');
            return $ep !== '' ? $ep : 'https://overpass-api.de/api/interpreter';
        } catch (Throwable) {
            return 'https://overpass-api.de/api/interpreter';
        }
    }

    private function buildQuery(string $q, string $city, string $category): string
    {
        // Map category hint to OSM tag filters
        $tagFilters = $this->categoryToOsmTags($category, $q);

        $areaFilter = '';
        if ($city !== '') {
            // Use area search by city name
            $safeCity   = addslashes($city);
            $areaFilter = "area[\"name\"=\"{$safeCity}\"][\"boundary\"=\"administrative\"]->.searchArea;";
            $areaIn     = '(area.searchArea)';
        } else {
            $areaIn = '';
        }

        // Build node/way queries for each tag filter
        $nodeQueries = [];
        foreach ($tagFilters as $filter) {
            $nodeQueries[] = "  node{$filter}{$areaIn};";
            $nodeQueries[] = "  way{$filter}{$areaIn};";
        }

        // If keyword given, also add name regex search across common amenity/office/shop types
        if ($q !== '' && empty($tagFilters)) {
            $safeQ         = addslashes($q);
            $nodeQueries[] = "  node[\"name\"~\"{$safeQ}\",i]{$areaIn};";
            $nodeQueries[] = "  way[\"name\"~\"{$safeQ}\",i]{$areaIn};";
        }

        if (empty($nodeQueries)) {
            // Fallback: search by name
            $safeQ         = addslashes($q ?: $city);
            $nodeQueries[] = "  node[\"name\"~\"{$safeQ}\",i]{$areaIn};";
            $nodeQueries[] = "  way[\"name\"~\"{$safeQ}\",i]{$areaIn};";
        }

        $union = implode("\n", $nodeQueries);

        return <<<OQL
[out:json][timeout:25];
{$areaFilter}
(
{$union}
);
out body;
OQL;
    }

    /**
     * Maps a category hint string to an array of Overpass QL tag filter strings.
     * e.g. "restaurant" → ['["amenity"="restaurant"]']
     */
    private function categoryToOsmTags(string $category, string $q): array
    {
        $combined = strtolower($category . ' ' . $q);

        $map = [
            'restaurant'  => ['["amenity"="restaurant"]'],
            'cafe'        => ['["amenity"="cafe"]'],
            'hotel'       => ['["tourism"="hotel"]', '["tourism"="hostel"]'],
            'hospital'    => ['["amenity"="hospital"]', '["amenity"="clinic"]'],
            'school'      => ['["amenity"="school"]', '["amenity"="college"]'],
            'bank'        => ['["amenity"="bank"]'],
            'pharmacy'    => ['["amenity"="pharmacy"]'],
            'supermarket' => ['["shop"="supermarket"]'],
            'gym'         => ['["leisure"="fitness_centre"]', '["leisure"="sports_centre"]'],
            'it'          => ['["office"="it"]', '["office"="company"][\"name\"~\"tech|software|it|digital\",i]'],
            'software'    => ['["office"="it"]', '["office"="company"][\"name\"~\"software|tech|digital\",i]'],
            'technology'  => ['["office"="it"]'],
            'office'      => ['["office"]'],
            'shop'        => ['["shop"]'],
        ];

        foreach ($map as $keyword => $tags) {
            if (str_contains($combined, $keyword)) {
                return $tags;
            }
        }

        return [];
    }

    private function normalise(array $el, string $q, string $city, string $category): ?array
    {
        $tags = $el['tags'] ?? [];
        $name = trim((string)($tags['name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $type     = $el['type'] ?? 'node';
        $osmId    = (string)($el['id'] ?? '');
        $sourceId = $type . '/' . $osmId;

        // Coordinates — nodes have lat/lon directly; ways have a centroid if requested
        $lat = isset($el['lat']) ? (float)$el['lat'] : null;
        $lon = isset($el['lon']) ? (float)$el['lon'] : null;
        if ($lat === null && isset($el['center'])) {
            $lat = (float)$el['center']['lat'];
            $lon = (float)$el['center']['lon'];
        }

        // Address components
        $houseNo  = trim((string)($tags['addr:housenumber'] ?? ''));
        $street   = trim((string)($tags['addr:street'] ?? ''));
        $addrCity = trim((string)($tags['addr:city'] ?? $tags['addr:suburb'] ?? '')) ?: $city;
        $state    = trim((string)($tags['addr:state'] ?? ''));
        $country  = trim((string)($tags['addr:country'] ?? 'India'));
        $pincode  = trim((string)($tags['addr:postcode'] ?? ''));

        $addressLine1 = trim($houseNo . ($houseNo !== '' && $street !== '' ? ', ' : '') . $street);
        $fullAddress  = implode(', ', array_filter([$addressLine1, $addrCity, $state, $pincode, $country]));

        // Contact
        $phone   = trim((string)($tags['phone'] ?? $tags['contact:phone'] ?? $tags['telephone'] ?? '')) ?: null;
        $website = trim((string)($tags['website'] ?? $tags['contact:website'] ?? $tags['url'] ?? '')) ?: null;
        $email   = trim((string)($tags['email'] ?? $tags['contact:email'] ?? '')) ?: null;

        // Social links
        $socialLinks = [];
        $socialMap   = [
            'facebook'          => ['contact:facebook', 'facebook'],
            'instagram'         => ['contact:instagram', 'instagram'],
            'twitter'           => ['contact:twitter', 'twitter'],
            'youtube'           => ['contact:youtube', 'youtube'],
            'linkedin'          => ['contact:linkedin', 'linkedin'],
            'whatsapp_channel'  => ['contact:whatsapp'],
            'telegram'          => ['contact:telegram'],
        ];
        foreach ($socialMap as $platform => $osmKeys) {
            foreach ($osmKeys as $osmKey) {
                $val = trim((string)($tags[$osmKey] ?? ''));
                if ($val !== '') {
                    $socialLinks[] = ['platform' => $platform, 'url' => $val];
                    break;
                }
            }
        }

        // Established year
        $establishedYear = null;
        $startDate = trim((string)($tags['start_date'] ?? ''));
        if ($startDate !== '' && preg_match('/^(\d{4})/', $startDate, $m)) {
            $yr = (int)$m[1];
            if ($yr >= 1800 && $yr <= (int)date('Y')) {
                $establishedYear = $yr;
            }
        }

        // Opening hours
        $hoursRaw  = trim((string)($tags['opening_hours'] ?? ''));
        $hoursParsed = $hoursRaw !== '' ? scraper_osm_parse_hours($hoursRaw) : [];

        // Category hint from OSM tags
        $categoryHint = $this->buildCategoryHint($tags);

        // All type tags for tag auto-matching
        $typesRaw = array_values(array_filter([
            $tags['amenity']  ?? null,
            $tags['office']   ?? null,
            $tags['shop']     ?? null,
            $tags['tourism']  ?? null,
            $tags['leisure']  ?? null,
            $tags['craft']    ?? null,
            $tags['healthcare'] ?? null,
        ]));

        // Build payload_json using actual migration 008 column names
        $payload = [
            'source'      => 'osm',
            'data_source' => 'scrape_osm',
            'group'       => [
                'name'             => $name,
                'tagline'          => null,
                'description'      => trim((string)($tags['description'] ?? '')) ?: null,
                'established_year' => $establishedYear,
                'website_url'      => $website,
                'email'            => $email,
                'parent_category_id' => 0,  // admin must map before import
                'price_range'      => null,
                'page_title'       => $name . ($addrCity !== '' ? ' - ' . $addrCity : ''),
                'meta_description' => null,
                'meta_keywords'    => implode(', ', $typesRaw) ?: null,
                'tag_ids'          => [],
                'tag_hints'        => $typesRaw,
            ],
            'branch'      => [
                'address_line1'   => $addressLine1 ?: null,
                'address_line2'   => null,
                'city'            => $addrCity ?: null,
                'state'           => $state ?: null,
                'country'         => $country,
                'pincode'         => $pincode ?: null,
                'latitude'        => $lat,
                'longitude'       => $lon,
                'phone_primary'   => $phone,
                'phone_secondary' => null,
                'whatsapp_number' => null,
            ],
            'hours'        => $hoursParsed,
            'hours_raw'    => $hoursRaw ?: null,
            'social_links' => $socialLinks,
        ];

        return [
            'source'        => 'osm',
            'source_id'     => $sourceId,
            'source_url'    => 'https://www.openstreetmap.org/' . $type . '/' . $osmId,
            'name'          => $name,
            'category_hint' => $categoryHint,
            'types_raw'     => $typesRaw,
            'city'          => $addrCity ?: null,
            'phone'         => $phone,
            'website'       => $website,
            'address'       => $fullAddress ?: null,
            'latitude'      => $lat,
            'longitude'     => $lon,
            'payload_json'  => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    private function buildCategoryHint(array $tags): ?string
    {
        $typeMap = [
            'amenity'    => $tags['amenity']  ?? null,
            'office'     => $tags['office']   ?? null,
            'shop'       => $tags['shop']     ?? null,
            'tourism'    => $tags['tourism']  ?? null,
            'leisure'    => $tags['leisure']  ?? null,
            'healthcare' => $tags['healthcare'] ?? null,
        ];
        foreach ($typeMap as $key => $val) {
            if ($val !== null && $val !== '') {
                return $key . ':' . $val;
            }
        }
        return null;
    }

    private function httpPost(string $url, array $postData): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($postData),
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_USERAGENT      => 'MyCityInfo-Scraper/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $result = curl_exec($ch);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($err !== '' || $result === false) {
            return null;
        }
        return (string)$result;
    }
}

/**
 * Parse OSM opening_hours tag into structured array.
 *
 * Handles common formats:
 *   24/7
 *   Mo-Fr 09:00-18:00
 *   Mo-Fr 09:00-18:00; Sa 10:00-14:00; Su off
 *   Mo,Tu,We,Th,Fr 09:00-17:00
 *
 * Returns array of 7 rows (Mon–Sun), each:
 *   ['day_of_week' => 'monday', 'opens_at' => '09:00:00', 'closes_at' => '18:00:00', 'is_closed' => 0]
 */
function scraper_osm_parse_hours(string $raw): array
{
    $dayNames = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
    $osmDays  = ['Mo'=>0,'Tu'=>1,'We'=>2,'Th'=>3,'Fr'=>4,'Sa'=>5,'Su'=>6];

    // Default: all closed
    $result = [];
    foreach ($dayNames as $d) {
        $result[$d] = ['day_of_week' => $d, 'opens_at' => null, 'closes_at' => null, 'is_closed' => 1];
    }

    $raw = trim($raw);
    if ($raw === '') {
        return array_values($result);
    }

    // Handle 24/7 special case
    if (strtolower($raw) === '24/7') {
        foreach ($dayNames as $d) {
            $result[$d] = ['day_of_week' => $d, 'opens_at' => '00:00:00', 'closes_at' => '23:59:00', 'is_closed' => 0];
        }
        return array_values($result);
    }

    // Split into rules by ;
    $rules = preg_split('/\s*;\s*/', $raw);
    foreach ((array)$rules as $rule) {
        $rule = trim((string)$rule);
        if ($rule === '') {
            continue;
        }

        // Match: "Mo-Fr 09:00-18:00" or "Mo,Tu,We 09:00-17:00" or "Sa off" or "Su closed"
        if (!preg_match('/^([A-Za-z,\- ]+?)\s+([\d:]+\s*-\s*[\d:]+|off|closed)$/i', $rule, $m)) {
            continue;
        }

        $dayPart  = trim($m[1]);
        $timePart = trim(strtolower($m[2]));

        $isClosed = ($timePart === 'off' || $timePart === 'closed') ? 1 : 0;
        $opensAt  = null;
        $closesAt = null;

        if (!$isClosed && preg_match('/^(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})$/', $timePart, $tm)) {
            $opensAt  = scraper_osm_format_time($tm[1]);
            $closesAt = scraper_osm_format_time($tm[2]);
        }

        // Expand day range/list
        $dayIndices = scraper_osm_expand_days($dayPart, $osmDays);
        foreach ($dayIndices as $idx) {
            $dayName = $dayNames[$idx];
            $result[$dayName] = [
                'day_of_week' => $dayName,
                'opens_at'    => $isClosed ? null : $opensAt,
                'closes_at'   => $isClosed ? null : $closesAt,
                'is_closed'   => $isClosed,
            ];
        }
    }

    return array_values($result);
}

function scraper_osm_format_time(string $t): string
{
    $parts = explode(':', $t);
    $h = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
    $m = str_pad($parts[1] ?? '00', 2, '0', STR_PAD_LEFT);
    return $h . ':' . $m . ':00';
}

function scraper_osm_expand_days(string $dayPart, array $osmDays): array
{
    $indices = [];
    // Split by comma first
    $segments = preg_split('/,/', $dayPart);
    foreach ((array)$segments as $seg) {
        $seg = trim((string)$seg);
        // Check if it's a range like Mo-Fr
        if (preg_match('/^([A-Za-z]{2})\s*-\s*([A-Za-z]{2})$/', $seg, $rm)) {
            $from = $osmDays[ucfirst(strtolower(substr($rm[1], 0, 2)))] ?? null;
            $to   = $osmDays[ucfirst(strtolower(substr($rm[2], 0, 2)))] ?? null;
            if ($from !== null && $to !== null) {
                for ($i = $from; $i <= $to; $i++) {
                    $indices[] = $i;
                }
            }
        } elseif (strlen($seg) === 2) {
            $key = ucfirst(strtolower($seg));
            if (isset($osmDays[$key])) {
                $indices[] = $osmDays[$key];
            }
        }
    }
    return $indices;
}
