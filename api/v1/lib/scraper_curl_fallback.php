<?php
declare(strict_types=1);

require_once __DIR__ . '/scraper_adapter_interface.php';

/**
 * cURL / HTML scraping fallback adapter.
 *
 * Fetches MCI_SCRAPER_CURL_TARGET URL and extracts business data using three
 * strategies in order of preference:
 *
 *   1. JSON-LD  — <script type="application/ld+json"> with @type LocalBusiness
 *   2. Microdata — itemtype="https://schema.org/LocalBusiness" (or subtype)
 *   3. CSS class conventions — elements with class="mci-business" (custom schema)
 *
 * The target URL receives q= and city= query params appended automatically.
 * Works with any page that embeds schema.org structured data — common on Indian
 * directories (JustDial, Sulekha, IndiaMart, etc.) and Google-indexed listings.
 *
 * Set MCI_SCRAPER_CURL_ENABLED=true and MCI_SCRAPER_CURL_TARGET=<url> in .env
 * Example targets:
 *   https://www.justdial.com/{City}/{Query}
 *   https://www.sulekha.com/{query}/{city}
 *   Any page with schema.org/LocalBusiness JSON-LD
 */
class ScraperCurlFallback implements ScraperAdapter
{
    public function sourceName(): string
    {
        return 'curl_scrape';
    }

    public function isAvailable(): bool
    {
        try {
            return api_env_flag('MCI_SCRAPER_CURL_ENABLED')
                && trim(api_env('MCI_SCRAPER_CURL_TARGET')) !== '';
        } catch (Throwable) {
            return false;
        }
    }

    public function monthlyLimit(): ?int
    {
        return null; // No external API limit
    }

    public function alertThreshold(): int
    {
        return 80;
    }

    public function search(array $params): array
    {
        $target = $this->targetUrl();
        if ($target === '') {
            return [];
        }

        $q    = trim((string)($params['q'] ?? ''));
        $city = trim((string)($params['city'] ?? ''));

        // Append query params to target URL
        $qp  = array_filter(['q' => $q, 'city' => $city]);
        $url = $target;
        if (!empty($qp)) {
            $sep  = str_contains($url, '?') ? '&' : '?';
            $url .= $sep . http_build_query($qp);
        }

        $html = $this->httpGet($url);
        if ($html === null) {
            return [];
        }

        // Strategy 1: JSON-LD
        $results = $this->extractJsonLd($html, $city);
        if (!empty($results)) {
            return $results;
        }

        // Strategy 2: Microdata (schema.org)
        $results = $this->extractMicrodata($html, $city);
        if (!empty($results)) {
            return $results;
        }

        // Strategy 3: CSS class conventions (mci-business)
        return $this->extractCssClasses($html, $city);
    }

    // ── Strategy 1: JSON-LD ───────────────────────────────────────────────

    private function extractJsonLd(string $html, string $cityHint): array
    {
        preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $html, $matches);

        $results = [];
        foreach ($matches[1] as $raw) {
            $json = json_decode(trim($raw), true);
            if (!is_array($json)) {
                continue;
            }

            // Handle @graph arrays
            $items = isset($json['@graph']) ? $json['@graph'] : [$json];

            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                // Check if it's a LocalBusiness type
                $type = (string)($item['@type'] ?? '');
                if (!$this->isLocalBusinessType($type)) {
                    continue;
                }
                $record = $this->normaliseJsonLd($item, $cityHint);
                if ($record !== null) {
                    $results[] = $record;
                }
            }

            // Also handle ItemList of businesses
            if (($json['@type'] ?? '') === 'ItemList' && !empty($json['itemListElement'])) {
                foreach ($json['itemListElement'] as $el) {
                    $item = $el['item'] ?? $el;
                    if (!is_array($item)) continue;
                    if (!$this->isLocalBusinessType((string)($item['@type'] ?? ''))) continue;
                    $record = $this->normaliseJsonLd($item, $cityHint);
                    if ($record !== null) {
                        $results[] = $record;
                    }
                }
            }
        }

        return $results;
    }

    private function isLocalBusinessType(string $type): bool
    {
        $localBusinessTypes = [
            'LocalBusiness', 'Restaurant', 'FoodEstablishment', 'CafeOrCoffeeShop',
            'FastFoodRestaurant', 'BarOrPub', 'Hotel', 'LodgingBusiness',
            'MedicalBusiness', 'Dentist', 'Physician', 'Hospital', 'Pharmacy',
            'HealthAndBeautyBusiness', 'BeautySalon', 'HairSalon', 'HealthClub',
            'Store', 'ShoppingCenter', 'AutoDealer', 'HomeAndConstructionBusiness',
            'LegalService', 'AccountingService', 'FinancialService',
            'EducationalOrganization', 'School', 'CollegeOrUniversity',
            'EntertainmentBusiness', 'SportsActivityLocation', 'GymOrHealthClub',
            'TravelAgency', 'RealEstateAgent', 'MovingCompany',
            'ITService', 'ProfessionalService', 'Organization',
        ];
        return in_array($type, $localBusinessTypes, true);
    }

    private function normaliseJsonLd(array $item, string $cityHint): ?array
    {
        $name = trim((string)($item['name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $address = $item['address'] ?? [];
        $geo     = $item['geo'] ?? [];

        // Address can be a string or PostalAddress object
        $line1    = null;
        $addrCity = null;
        $state    = null;
        $pincode  = null;
        $fullAddr = null;

        if (is_string($address) && $address !== '') {
            $fullAddr = $address;
        } elseif (is_array($address)) {
            $line1    = trim((string)($address['streetAddress'] ?? '')) ?: null;
            $addrCity = trim((string)($address['addressLocality'] ?? '')) ?: null;
            $state    = trim((string)($address['addressRegion'] ?? '')) ?: null;
            $pincode  = trim((string)($address['postalCode'] ?? '')) ?: null;
        }

        $addrCity = $addrCity ?: ($cityHint ?: null);
        $fullAddr = $fullAddr ?: implode(', ', array_filter([$line1, $addrCity, $state, $pincode, 'India']));

        $lat = isset($geo['latitude'])  ? (float)$geo['latitude']  : null;
        $lon = isset($geo['longitude']) ? (float)$geo['longitude'] : null;

        // Contact
        $phone   = trim((string)($item['telephone'] ?? '')) ?: null;
        $website = trim((string)($item['url'] ?? '')) ?: null;
        $email   = trim((string)($item['email'] ?? '')) ?: null;

        // Category / type
        $typesRaw     = [];
        $categoryHint = null;
        $type         = (string)($item['@type'] ?? '');
        if ($type !== '' && $type !== 'LocalBusiness') {
            $typesRaw[]   = $type;
            $categoryHint = $type;
        }
        if (!empty($item['servesCuisine'])) {
            $cuisine = is_array($item['servesCuisine']) ? implode(', ', $item['servesCuisine']) : $item['servesCuisine'];
            $typesRaw[] = (string)$cuisine;
        }

        // Hours
        $hoursArray = [];
        $hoursRaw   = null;
        if (!empty($item['openingHours'])) {
            $oh       = is_array($item['openingHours']) ? $item['openingHours'] : [$item['openingHours']];
            $hoursRaw = implode('; ', $oh);
            $hoursArray = $this->parseSchemaOrgHours($oh);
        } elseif (!empty($item['openingHoursSpecification'])) {
            $hoursArray = $this->parseOpeningHoursSpecification($item['openingHoursSpecification']);
        }

        // Social links
        $socialLinks = [];
        $sameAs = $item['sameAs'] ?? [];
        if (is_string($sameAs)) {
            $sameAs = [$sameAs];
        }
        $platformPatterns = [
            'facebook'  => 'facebook.com',
            'instagram' => 'instagram.com',
            'twitter'   => 'twitter.com',
            'youtube'   => 'youtube.com',
            'linkedin'  => 'linkedin.com',
        ];
        foreach ($sameAs as $link) {
            foreach ($platformPatterns as $platform => $pattern) {
                if (str_contains(strtolower((string)$link), $pattern)) {
                    $socialLinks[] = ['platform' => $platform, 'url' => (string)$link];
                    break;
                }
            }
        }

        // Source ID: use @id or url or md5 fallback
        $sourceId = md5($name . '|' . ($addrCity ?? '') . '|' . ($line1 ?? ''));
        $sourceUrl = trim((string)($item['@id'] ?? '')) ?: $website;

        $payload = [
            'source'      => 'curl_scrape',
            'data_source' => 'scrape_html',
            'group' => [
                'name'               => $name,
                'tagline'            => null,
                'description'        => trim((string)($item['description'] ?? '')) ?: null,
                'established_year'   => null,
                'website_url'        => $website,
                'email'              => $email,
                'parent_category_id' => 0,
                'price_range'        => null,
                'page_title'         => $name . ($addrCity ? ' - ' . $addrCity : ''),
                'meta_description'   => null,
                'meta_keywords'      => implode(', ', $typesRaw) ?: null,
                'tag_ids'            => [],
                'tag_hints'          => $typesRaw,
            ],
            'branch' => [
                'address_line1'   => $line1,
                'address_line2'   => null,
                'city'            => $addrCity,
                'state'           => $state,
                'country'         => 'India',
                'pincode'         => $pincode,
                'latitude'        => $lat,
                'longitude'       => $lon,
                'phone_primary'   => $phone,
                'phone_secondary' => null,
                'whatsapp_number' => null,
            ],
            'hours'        => $hoursArray,
            'hours_raw'    => $hoursRaw,
            'social_links' => $socialLinks,
        ];

        return [
            'source'        => 'curl_scrape',
            'source_id'     => $sourceId,
            'source_url'    => $sourceUrl,
            'name'          => $name,
            'category_hint' => $categoryHint,
            'types_raw'     => $typesRaw,
            'city'          => $addrCity,
            'phone'         => $phone,
            'website'       => $website,
            'address'       => $fullAddr ?: null,
            'latitude'      => $lat,
            'longitude'     => $lon,
            'payload_json'  => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    /**
     * Parse schema.org openingHours strings: "Mo-Fr 09:00-18:00", "Sa 10:00-14:00", "24/7"
     */
    private function parseSchemaOrgHours(array $specs): array
    {
        $dayAbbr = [
            'Mo' => 'monday', 'Tu' => 'tuesday', 'We' => 'wednesday',
            'Th' => 'thursday', 'Fr' => 'friday', 'Sa' => 'saturday', 'Su' => 'sunday',
        ];
        $allDays = array_values($dayAbbr);
        $map     = [];

        foreach ($specs as $spec) {
            $spec = trim((string)$spec);
            if (strtolower($spec) === '24/7') {
                foreach ($allDays as $d) {
                    $map[$d] = ['opens_at' => '00:00', 'closes_at' => '23:59', 'is_closed' => false];
                }
                continue;
            }
            // e.g. "Mo-Fr 09:00-18:00" or "Mo Tu 09:00-18:00"
            if (!preg_match('/^([\w\s\-]+)\s+(\d{2}:\d{2})-(\d{2}:\d{2})$/', $spec, $m)) {
                continue;
            }
            $daysPart = trim($m[1]);
            $opens    = $m[2];
            $closes   = $m[3];

            $days = [];
            // Range: Mo-Fr
            if (preg_match('/^([A-Z][a-z])-([A-Z][a-z])$/', $daysPart, $r)) {
                $keys    = array_keys($dayAbbr);
                $start   = array_search($r[1], $keys, true);
                $end     = array_search($r[2], $keys, true);
                if ($start !== false && $end !== false) {
                    for ($i = $start; $i <= $end; $i++) {
                        $days[] = $dayAbbr[$keys[$i]];
                    }
                }
            } else {
                // Individual: "Mo Tu We"
                foreach (preg_split('/[\s,]+/', $daysPart) as $abbr) {
                    if (isset($dayAbbr[$abbr])) {
                        $days[] = $dayAbbr[$abbr];
                    }
                }
            }

            foreach ($days as $d) {
                $map[$d] = ['opens_at' => $opens, 'closes_at' => $closes, 'is_closed' => false];
            }
        }

        $result = [];
        foreach ($allDays as $d) {
            $result[] = [
                'day_of_week' => $d,
                'opens_at'    => $map[$d]['opens_at']  ?? null,
                'closes_at'   => $map[$d]['closes_at'] ?? null,
                'is_closed'   => !isset($map[$d]),
            ];
        }
        return $result;
    }

    /**
     * Parse openingHoursSpecification array:
     * [{"@type":"OpeningHoursSpecification","dayOfWeek":"Monday","opens":"09:00","closes":"18:00"}]
     */
    private function parseOpeningHoursSpecification(array $specs): array
    {
        $allDays = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
        $map     = [];

        foreach ($specs as $spec) {
            $rawDays = $spec['dayOfWeek'] ?? [];
            if (is_string($rawDays)) {
                $rawDays = [$rawDays];
            }
            $opens  = trim((string)($spec['opens']  ?? ''));
            $closes = trim((string)($spec['closes'] ?? ''));

            foreach ($rawDays as $rawDay) {
                $day = strtolower(trim(str_replace('https://schema.org/', '', (string)$rawDay)));
                if (in_array($day, $allDays, true)) {
                    $map[$day] = ['opens_at' => $opens ?: null, 'closes_at' => $closes ?: null, 'is_closed' => false];
                }
            }
        }

        $result = [];
        foreach ($allDays as $d) {
            $result[] = [
                'day_of_week' => $d,
                'opens_at'    => $map[$d]['opens_at']  ?? null,
                'closes_at'   => $map[$d]['closes_at'] ?? null,
                'is_closed'   => !isset($map[$d]),
            ];
        }
        return $result;
    }

    // ── Strategy 2: Microdata ─────────────────────────────────────────────

    private function extractMicrodata(string $html, string $cityHint): array
    {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);

        // Find elements with schema.org LocalBusiness itemtype
        $nodes = $xpath->query(
            '//*[contains(@itemtype,"schema.org/LocalBusiness") or
                 contains(@itemtype,"schema.org/Restaurant") or
                 contains(@itemtype,"schema.org/Store") or
                 contains(@itemtype,"schema.org/Hotel") or
                 contains(@itemtype,"schema.org/MedicalBusiness")]'
        );

        if ($nodes === false || $nodes->length === 0) {
            return [];
        }

        $results = [];
        foreach ($nodes as $node) {
            $record = $this->normaliseMicrodata($node, $xpath, $cityHint);
            if ($record !== null) {
                $results[] = $record;
            }
        }
        return $results;
    }

    private function normaliseMicrodata(DOMElement $node, DOMXPath $xpath, string $cityHint): ?array
    {
        $name = $this->microdataProp($node, $xpath, 'name');
        if ($name === null) {
            return null;
        }

        $phone   = $this->microdataProp($node, $xpath, 'telephone');
        $website = $this->microdataProp($node, $xpath, 'url');
        $email   = $this->microdataProp($node, $xpath, 'email');

        // Address
        $addrNode = $xpath->query('.//*[@itemprop="address"]', $node)->item(0);
        $line1    = null;
        $addrCity = null;
        $state    = null;
        $pincode  = null;

        if ($addrNode) {
            $line1    = $this->microdataProp($addrNode, $xpath, 'streetAddress');
            $addrCity = $this->microdataProp($addrNode, $xpath, 'addressLocality');
            $state    = $this->microdataProp($addrNode, $xpath, 'addressRegion');
            $pincode  = $this->microdataProp($addrNode, $xpath, 'postalCode');
        }
        $addrCity = $addrCity ?: ($cityHint ?: null);

        $latRaw = $this->microdataProp($node, $xpath, 'latitude');
        $lonRaw = $this->microdataProp($node, $xpath, 'longitude');
        $lat    = $latRaw !== null ? (float)$latRaw : null;
        $lon    = $lonRaw !== null ? (float)$lonRaw : null;

        $fullAddr = implode(', ', array_filter([$line1, $addrCity, $state, $pincode, 'India']));
        $sourceId = md5($name . '|' . ($addrCity ?? '') . '|' . ($line1 ?? ''));

        $payload = [
            'source'      => 'curl_scrape',
            'data_source' => 'scrape_html',
            'group' => [
                'name'               => $name,
                'tagline'            => null,
                'description'        => $this->microdataProp($node, $xpath, 'description'),
                'established_year'   => null,
                'website_url'        => $website,
                'email'              => $email,
                'parent_category_id' => 0,
                'price_range'        => null,
                'page_title'         => $name . ($addrCity ? ' - ' . $addrCity : ''),
                'meta_description'   => null,
                'meta_keywords'      => null,
                'tag_ids'            => [],
                'tag_hints'          => [],
            ],
            'branch' => [
                'address_line1'   => $line1,
                'address_line2'   => null,
                'city'            => $addrCity,
                'state'           => $state,
                'country'         => 'India',
                'pincode'         => $pincode,
                'latitude'        => $lat,
                'longitude'       => $lon,
                'phone_primary'   => $phone,
                'phone_secondary' => null,
                'whatsapp_number' => null,
            ],
            'hours'        => [],
            'hours_raw'    => null,
            'social_links' => [],
        ];

        return [
            'source'        => 'curl_scrape',
            'source_id'     => $sourceId,
            'source_url'    => null,
            'name'          => $name,
            'category_hint' => null,
            'types_raw'     => [],
            'city'          => $addrCity,
            'phone'         => $phone,
            'website'       => $website,
            'address'       => $fullAddr ?: null,
            'latitude'      => $lat,
            'longitude'     => $lon,
            'payload_json'  => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    private function microdataProp(DOMNode $node, DOMXPath $xpath, string $prop): ?string
    {
        $nodes = $xpath->query('.//*[@itemprop="' . $prop . '"]', $node);
        if ($nodes === false || $nodes->length === 0) {
            return null;
        }
        $el = $nodes->item(0);
        // For links use href, for meta use content, otherwise textContent
        if ($el instanceof DOMElement) {
            if ($el->hasAttribute('content')) {
                return trim($el->getAttribute('content')) ?: null;
            }
            if ($el->hasAttribute('href')) {
                return trim($el->getAttribute('href')) ?: null;
            }
        }
        return trim($el->textContent) ?: null;
    }

    // ── Strategy 3: CSS class conventions ────────────────────────────────

    private function extractCssClasses(string $html, string $cityHint): array
    {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);
        $items = $xpath->query('//*[contains(@class,"mci-business")]');
        if ($items === false || $items->length === 0) {
            return [];
        }

        $results = [];
        foreach ($items as $node) {
            $name = $this->cssField($node, $xpath, 'mci-name');
            if ($name === null) {
                continue;
            }

            $phone        = $this->cssField($node, $xpath, 'mci-phone');
            $website      = $this->cssField($node, $xpath, 'mci-website');
            $address      = $this->cssField($node, $xpath, 'mci-address');
            $city         = $this->cssField($node, $xpath, 'mci-city') ?? $cityHint ?: null;
            $categoryHint = $this->cssField($node, $xpath, 'mci-category');
            $state        = $this->cssField($node, $xpath, 'mci-state');
            $pincode      = $this->cssField($node, $xpath, 'mci-pincode');
            $line1        = $this->cssField($node, $xpath, 'mci-address-line1');
            $latRaw       = $this->cssField($node, $xpath, 'mci-lat');
            $lonRaw       = $this->cssField($node, $xpath, 'mci-lon');

            $lat = $latRaw !== null ? (float)$latRaw : null;
            $lon = $lonRaw !== null ? (float)$lonRaw : null;

            $sourceId = trim((string)($node instanceof DOMElement ? $node->getAttribute('data-source-id') : ''));
            if ($sourceId === '') {
                $sourceId = md5($name . '|' . ($city ?? '') . '|' . ($address ?? ''));
            }

            $typesRaw    = $categoryHint !== null ? [$categoryHint] : [];
            $fullAddress = $address ?? implode(', ', array_filter([$line1, $city, $state, $pincode, 'India']));

            $payload = [
                'source'      => 'curl_scrape',
                'data_source' => 'scrape_html',
                'group' => [
                    'name'               => $name,
                    'tagline'            => null,
                    'description'        => $this->cssField($node, $xpath, 'mci-description'),
                    'established_year'   => null,
                    'website_url'        => $website,
                    'email'              => $this->cssField($node, $xpath, 'mci-email'),
                    'parent_category_id' => 0,
                    'price_range'        => null,
                    'page_title'         => $name . ($city ? ' - ' . $city : ''),
                    'meta_description'   => null,
                    'meta_keywords'      => $categoryHint,
                    'tag_ids'            => [],
                    'tag_hints'          => $typesRaw,
                ],
                'branch' => [
                    'address_line1'   => $line1 ?? $address,
                    'address_line2'   => null,
                    'city'            => $city,
                    'state'           => $state,
                    'country'         => 'India',
                    'pincode'         => $pincode,
                    'latitude'        => $lat,
                    'longitude'       => $lon,
                    'phone_primary'   => $phone,
                    'phone_secondary' => null,
                    'whatsapp_number' => $this->cssField($node, $xpath, 'mci-whatsapp'),
                ],
                'hours'        => [],
                'hours_raw'    => null,
                'social_links' => [],
            ];

            $results[] = [
                'source'        => 'curl_scrape',
                'source_id'     => $sourceId,
                'source_url'    => null,
                'name'          => $name,
                'category_hint' => $categoryHint,
                'types_raw'     => $typesRaw,
                'city'          => $city,
                'phone'         => $phone,
                'website'       => $website,
                'address'       => $fullAddress ?: null,
                'latitude'      => $lat,
                'longitude'     => $lon,
                'payload_json'  => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ];
        }
        return $results;
    }

    private function cssField(DOMNode $node, DOMXPath $xpath, string $class): ?string
    {
        $nodes = $xpath->query('.//*[contains(@class,"' . $class . '")]', $node);
        if ($nodes === false || $nodes->length === 0) {
            return null;
        }
        $text = trim($nodes->item(0)->textContent);
        return $text !== '' ? $text : null;
    }

    private function targetUrl(): string
    {
        try {
            return trim(api_env('MCI_SCRAPER_CURL_TARGET'));
        } catch (Throwable) {
            return '';
        }
    }

    // =========================================================================
    // Public methods for URL import tool (direct URL extraction + crawling)
    // =========================================================================

    /**
     * Fetch a single business detail page and extract all business records.
     * Used by the URL import tool — does not append q=/city= params.
     * Tries JSON-LD → microdata → CSS classes in order.
     *
     * @return array  Array of normalised records (usually 1, but JSON-LD @graph may yield more)
     */
    public function extractFromUrl(string $url): array
    {
        $html = $this->httpGet($url);
        if ($html === null) {
            return [];
        }
        $cityHint = '';
        $results = $this->extractJsonLd($html, $cityHint);
        if (!empty($results)) {
            return $results;
        }
        $results = $this->extractMicrodata($html, $cityHint);
        if (!empty($results)) {
            return $results;
        }
        return $this->extractCssClasses($html, $cityHint);
    }

    /**
     * Fetch a directory/listing index page and return all hrefs that match
     * an optional substring pattern. Returns absolute URLs, capped at $limit.
     *
     * @param string $indexUrl  The listing/directory page URL
     * @param string $pattern   Optional substring every discovered URL must contain
     * @param int    $limit     Max URLs to return (default 20)
     * @return string[]
     */
    public function discoverUrls(string $indexUrl, string $pattern = '', int $limit = 20): array
    {
        $html = $this->httpGet($indexUrl);
        if ($html === null) {
            return [];
        }

        $base = $this->baseUrl($indexUrl);

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);
        $links = $xpath->query('//a[@href]');
        $found = [];
        $seen  = [];

        foreach ($links as $link) {
            if (!($link instanceof DOMElement)) {
                continue;
            }
            $href = trim($link->getAttribute('href'));
            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, 'javascript:')) {
                continue;
            }
            // Make absolute URL
            if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
                $abs = $href;
            } elseif (str_starts_with($href, '//')) {
                $abs = 'https:' . $href;
            } elseif (str_starts_with($href, '/')) {
                $abs = rtrim($base, '/') . $href;
            } else {
                $abs = rtrim($base, '/') . '/' . $href;
            }
            // Strip fragment
            if (($fragPos = strpos($abs, '#')) !== false) {
                $abs = substr($abs, 0, $fragPos);
            }
            // Pattern filter
            if ($pattern !== '' && !str_contains($abs, $pattern)) {
                continue;
            }
            // Skip the index URL itself
            if (rtrim($abs, '/') === rtrim($indexUrl, '/')) {
                continue;
            }
            if (isset($seen[$abs])) {
                continue;
            }
            $seen[$abs] = true;
            $found[] = $abs;
            if (count($found) >= $limit) {
                break;
            }
        }

        return $found;
    }

    private function baseUrl(string $url): string
    {
        $parsed = parse_url($url);
        return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
    }

    private function httpGet(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: en-IN,en;q=0.9',
                'Cache-Control: no-cache',
            ],
        ]);
        $result = curl_exec($ch);
        $err    = curl_error($ch);
        curl_close($ch);
        return ($err !== '' || $result === false) ? null : (string)$result;
    }
}
