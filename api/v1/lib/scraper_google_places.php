<?php
declare(strict_types=1);

require_once __DIR__ . '/scraper_adapter_interface.php';

/**
 * Google Places API adapter (Text Search + optional Place Details).
 *
 * Paid tier: $200/month free credit → ~28,500 Text Search calls/month free.
 * Requires a credit card on file.
 * Sign up at: https://console.cloud.google.com
 * Set MCI_SCRAPER_GOOGLE_ENABLED=true and MCI_GOOGLE_PLACES_API_KEY in .env
 *
 * Set MCI_SCRAPER_GOOGLE_FETCH_DETAILS=true to make a second Details call per result
 * for richer data (hours, price_level, website). Doubles call count.
 */
class ScraperGooglePlaces implements ScraperAdapter
{
    public function sourceName(): string
    {
        return 'google_places';
    }

    public function isAvailable(): bool
    {
        try {
            return api_env_flag('MCI_SCRAPER_GOOGLE_ENABLED')
                && api_env('MCI_GOOGLE_PLACES_API_KEY') !== '';
        } catch (Throwable) {
            return false;
        }
    }

    public function monthlyLimit(): ?int
    {
        try {
            $v = (int) api_env('MCI_GOOGLE_MONTHLY_LIMIT');
            return $v > 0 ? $v : 28500;
        } catch (Throwable) {
            return 28500;
        }
    }

    public function alertThreshold(): int
    {
        try {
            $v = (int) api_env('MCI_GOOGLE_ALERT_THRESHOLD');
            return ($v > 0 && $v <= 100) ? $v : 80;
        } catch (Throwable) {
            return 80;
        }
    }

    public function search(array $params): array
    {
        $q    = trim((string)($params['q'] ?? ''));
        $city = trim((string)($params['city'] ?? ''));

        $query = trim($q . ($city !== '' ? ' in ' . $city : '') . ' India');
        if ($query === 'India') {
            return [];
        }

        $apiKey = $this->apiKey();
        if ($apiKey === '') {
            return [];
        }

        // Google Places Text Search
        $url = 'https://maps.googleapis.com/maps/api/place/textsearch/json'
             . '?query=' . rawurlencode($query)
             . '&region=in'
             . '&key=' . rawurlencode($apiKey);

        $raw = $this->httpGet($url);
        if ($raw === null) {
            return [];
        }

        $json = json_decode($raw, true);
        if (!is_array($json) || ($json['status'] ?? '') === 'REQUEST_DENIED') {
            return [];
        }

        $fetchDetails = $this->shouldFetchDetails();
        $results      = [];

        foreach ($json['results'] ?? [] as $item) {
            $details = null;
            if ($fetchDetails && !empty($item['place_id'])) {
                $details = $this->fetchDetails($item['place_id'], $apiKey);
            }
            $record = $this->normalise($item, $details, $city);
            if ($record !== null) {
                $results[] = $record;
            }
        }
        return $results;
    }

    private function fetchDetails(string $placeId, string $apiKey): ?array
    {
        $fields = implode(',', [
            'place_id', 'name', 'formatted_address', 'formatted_phone_number',
            'international_phone_number', 'website', 'opening_hours',
            'address_components', 'geometry', 'price_level', 'types',
        ]);

        $url = 'https://maps.googleapis.com/maps/api/place/details/json'
             . '?place_id=' . rawurlencode($placeId)
             . '&fields=' . rawurlencode($fields)
             . '&key=' . rawurlencode($apiKey);

        $raw = $this->httpGet($url);
        if ($raw === null) {
            return null;
        }

        $json = json_decode($raw, true);
        if (!is_array($json) || ($json['status'] ?? '') !== 'OK') {
            return null;
        }
        return $json['result'] ?? null;
    }

    private function normalise(array $item, ?array $details, string $city): ?array
    {
        // Prefer details data when available
        $src  = $details ?? $item;
        $name = trim((string)($src['name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $sourceId = (string)($src['place_id'] ?? $item['place_id'] ?? '');
        $geo      = $src['geometry']['location'] ?? $item['geometry']['location'] ?? [];
        $lat      = isset($geo['lat']) ? (float)$geo['lat'] : null;
        $lon      = isset($geo['lng']) ? (float)$geo['lng'] : null;

        $phone   = trim((string)($src['formatted_phone_number'] ?? $src['international_phone_number'] ?? '')) ?: null;
        $website = trim((string)($src['website'] ?? '')) ?: null;

        // Parse address components for structured fields
        $components = $src['address_components'] ?? [];
        $line1       = null;
        $addrCity    = null;
        $state       = null;
        $pincode     = null;

        $streetNumber = '';
        $route        = '';
        foreach ($components as $comp) {
            $types = $comp['types'] ?? [];
            if (in_array('street_number', $types, true)) {
                $streetNumber = $comp['long_name'] ?? '';
            } elseif (in_array('route', $types, true)) {
                $route = $comp['long_name'] ?? '';
            } elseif (in_array('locality', $types, true)) {
                $addrCity = $comp['long_name'] ?? null;
            } elseif (in_array('administrative_area_level_1', $types, true)) {
                $state = $comp['long_name'] ?? null;
            } elseif (in_array('postal_code', $types, true)) {
                $pincode = $comp['long_name'] ?? null;
            }
        }
        $line1 = trim($streetNumber . ($route !== '' ? ' ' . $route : '')) ?: null;
        $addrCity = $addrCity ?: ($city ?: null);

        // Fallback to formatted_address if components not available
        $formattedAddress = (string)($src['formatted_address'] ?? $item['formatted_address'] ?? '');
        $fullAddress = $line1
            ? implode(', ', array_filter([$line1, $addrCity, $state, $pincode, 'India']))
            : ($formattedAddress ?: null);

        // Types / category
        $typesRaw     = array_map('strval', $src['types'] ?? $item['types'] ?? []);
        $categoryHint = $typesRaw[0] ?? null;

        // Price range
        $priceRange = null;
        if (isset($src['price_level'])) {
            $priceRange = str_repeat('₹', (int)$src['price_level']) ?: null;
        }

        // Opening hours
        $hoursArray = [];
        $hoursRaw   = null;
        if (!empty($src['opening_hours'])) {
            $oh        = $src['opening_hours'];
            $hoursRaw  = implode("\n", $oh['weekday_text'] ?? []) ?: null;
            $hoursArray = $this->parseGoogleHours($oh['periods'] ?? []);
        }

        $payload = [
            'source'      => 'google_places',
            'data_source' => 'scrape_google',
            'group' => [
                'name'               => $name,
                'tagline'            => null,
                'description'        => null,
                'established_year'   => null,
                'website_url'        => $website,
                'email'              => null,
                'parent_category_id' => 0,
                'price_range'        => $priceRange,
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
            'social_links' => [],
        ];

        return [
            'source'        => 'google_places',
            'source_id'     => $sourceId,
            'source_url'    => $sourceId !== '' ? 'https://maps.google.com/?cid=' . $sourceId : null,
            'name'          => $name,
            'category_hint' => $categoryHint,
            'types_raw'     => $typesRaw,
            'city'          => $addrCity,
            'phone'         => $phone,
            'website'       => $website,
            'address'       => $fullAddress ?: null,
            'latitude'      => $lat,
            'longitude'     => $lon,
            'payload_json'  => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    /**
     * Parse Google Places periods into 7-day array.
     *
     * Each period: {"open":{"day":1,"time":"0900"},"close":{"day":1,"time":"1800"}}
     * day: 0=Sunday, 1=Monday, ..., 6=Saturday
     */
    private function parseGoogleHours(array $periods): array
    {
        $dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $map      = array_fill_keys($dayNames, null);

        // 24/7 check: single period with open day=0 time=0000 and no close
        if (count($periods) === 1 && !isset($periods[0]['close'])) {
            foreach ($dayNames as $d) {
                $map[$d] = ['opens_at' => '00:00', 'closes_at' => '23:59', 'is_closed' => false];
            }
        } else {
            foreach ($periods as $period) {
                $dayIndex = (int)($period['open']['day'] ?? -1);
                if ($dayIndex < 0 || $dayIndex > 6) {
                    continue;
                }
                $dayName  = $dayNames[$dayIndex];
                $opensAt  = $this->formatGoogleTime($period['open']['time'] ?? '');
                $closesAt = $this->formatGoogleTime($period['close']['time'] ?? '');
                $map[$dayName] = ['opens_at' => $opensAt, 'closes_at' => $closesAt, 'is_closed' => false];
            }
        }

        $result = [];
        foreach ($dayNames as $d) {
            // Reorder: start from Monday
        }
        // Standard order: monday first
        $ordered = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        foreach ($ordered as $d) {
            $result[] = [
                'day_of_week' => $d,
                'opens_at'    => $map[$d]['opens_at'] ?? null,
                'closes_at'   => $map[$d]['closes_at'] ?? null,
                'is_closed'   => $map[$d] === null,
            ];
        }
        return $result;
    }

    /** Convert "0930" → "09:30" */
    private function formatGoogleTime(string $t): ?string
    {
        if (strlen($t) === 4 && ctype_digit($t)) {
            return substr($t, 0, 2) . ':' . substr($t, 2, 2);
        }
        return null;
    }

    private function shouldFetchDetails(): bool
    {
        try {
            return api_env_flag('MCI_SCRAPER_GOOGLE_FETCH_DETAILS');
        } catch (Throwable) {
            return true;
        }
    }

    private function apiKey(): string
    {
        try {
            return api_env('MCI_GOOGLE_PLACES_API_KEY');
        } catch (Throwable) {
            return '';
        }
    }

    private function httpGet(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'MyCityInfo-Scraper/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $result = curl_exec($ch);
        $err    = curl_error($ch);
        curl_close($ch);
        return ($err !== '' || $result === false) ? null : (string)$result;
    }
}
