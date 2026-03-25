<?php
declare(strict_types=1);

require_once __DIR__ . '/scraper_adapter_interface.php';

/**
 * Foursquare Places API v3 adapter.
 *
 * Free tier: 1,000 API calls/day (~30,000/month). No credit card required.
 * Sign up at: https://foursquare.com/developers/
 * Set MCI_SCRAPER_FOURSQUARE_ENABLED=true and MCI_FOURSQUARE_API_KEY in .env
 *
 * API docs: https://docs.foursquare.com/developer/reference/place-search
 */
class ScraperFoursquare implements ScraperAdapter
{
    public function sourceName(): string
    {
        return 'foursquare';
    }

    public function isAvailable(): bool
    {
        try {
            return api_env_flag('MCI_SCRAPER_FOURSQUARE_ENABLED')
                && api_env('MCI_FOURSQUARE_API_KEY') !== '';
        } catch (Throwable) {
            return false;
        }
    }

    public function monthlyLimit(): ?int
    {
        try {
            $v = (int) api_env('MCI_FOURSQUARE_MONTHLY_LIMIT');
            return $v > 0 ? $v : 30000;
        } catch (Throwable) {
            return 30000;
        }
    }

    public function alertThreshold(): int
    {
        try {
            $v = (int) api_env('MCI_FOURSQUARE_ALERT_THRESHOLD');
            return ($v > 0 && $v <= 100) ? $v : 80;
        } catch (Throwable) {
            return 80;
        }
    }

    public function search(array $params): array
    {
        $q    = trim((string)($params['q'] ?? ''));
        $city = trim((string)($params['city'] ?? ''));

        if ($q === '' && $city === '') {
            return [];
        }

        $apiKey = $this->apiKey();
        if ($apiKey === '') {
            return [];
        }

        $near  = $city !== '' ? $city . ', India' : 'India';
        $query = $q !== '' ? $q : $city;

        $fields = 'fsq_id,name,location,tel,website,categories,hours,rating,price,email,social_media,stats';

        $url = 'https://api.foursquare.com/v3/places/search'
             . '?query='  . rawurlencode($query)
             . '&near='   . rawurlencode($near)
             . '&limit=20'
             . '&fields=' . rawurlencode($fields);

        $raw = $this->httpGet($url, $apiKey);
        if ($raw === null) {
            return [];
        }

        $json = json_decode($raw, true);
        if (!is_array($json) || !isset($json['results'])) {
            return [];
        }

        $results = [];
        foreach ($json['results'] as $item) {
            $record = $this->normalise($item, $city);
            if ($record !== null) {
                $results[] = $record;
            }
        }
        return $results;
    }

    private function normalise(array $item, string $city): ?array
    {
        $name = trim((string)($item['name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $sourceId = (string)($item['fsq_id'] ?? '');
        $location = $item['location'] ?? [];

        // Address
        $line1    = trim((string)($location['address'] ?? '')) ?: null;
        $addrCity = trim((string)($location['locality'] ?? $location['region'] ?? '')) ?: ($city ?: null);
        $state    = trim((string)($location['region'] ?? '')) ?: null;
        $pincode  = trim((string)($location['postcode'] ?? '')) ?: null;
        $country  = trim((string)($location['country'] ?? 'IN'));

        // Only keep Indian results
        if ($country !== 'IN' && strtolower($country) !== 'india') {
            return null;
        }

        $fullAddress = trim((string)($location['formatted_address'] ?? ''))
            ?: implode(', ', array_filter([$line1, $addrCity, $state, $pincode, 'India']));

        $lat = isset($item['geocodes']['main']['latitude'])  ? (float)$item['geocodes']['main']['latitude']  : null;
        $lon = isset($item['geocodes']['main']['longitude']) ? (float)$item['geocodes']['main']['longitude'] : null;

        $phone   = trim((string)($item['tel'] ?? '')) ?: null;
        $website = trim((string)($item['website'] ?? '')) ?: null;
        $email   = trim((string)($item['email'] ?? '')) ?: null;

        // Categories
        $typesRaw     = [];
        $categoryHint = null;
        foreach ($item['categories'] ?? [] as $cat) {
            $label = trim((string)($cat['name'] ?? ''));
            if ($label !== '') {
                $typesRaw[] = $label;
            }
        }
        $categoryHint = $typesRaw[0] ?? null;

        // Price range
        $priceMap   = [1 => 'free', 2 => 'moderate', 3 => 'pricey', 4 => 'ultra'];
        $priceRange = $priceMap[$item['price'] ?? 0] ?? null;

        // Opening hours
        $hoursArray = [];
        $hoursRaw   = null;
        if (!empty($item['hours'])) {
            $oh = $item['hours'];
            if (!empty($oh['display'])) {
                $hoursRaw = $oh['display'];
            }
            if (!empty($oh['regular'])) {
                $hoursArray = $this->parseFoursquareHours($oh['regular']);
            }
        }

        // Social links
        $socialLinks = [];
        $social      = $item['social_media'] ?? [];
        $platformMap = [
            'facebook_id'  => 'facebook',
            'instagram'    => 'instagram',
            'twitter'      => 'twitter',
        ];
        foreach ($platformMap as $field => $platform) {
            $val = trim((string)($social[$field] ?? ''));
            if ($val !== '') {
                $url = match($platform) {
                    'facebook'  => 'https://www.facebook.com/' . $val,
                    'instagram' => 'https://www.instagram.com/' . $val,
                    'twitter'   => 'https://twitter.com/' . $val,
                    default     => $val,
                };
                $socialLinks[] = ['platform' => $platform, 'url' => $url];
            }
        }

        $payload = [
            'source'      => 'foursquare',
            'data_source' => 'scrape_foursquare',
            'group' => [
                'name'               => $name,
                'tagline'            => null,
                'description'        => null,
                'established_year'   => null,
                'website_url'        => $website,
                'email'              => $email,
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
            'social_links' => $socialLinks,
        ];

        return [
            'source'        => 'foursquare',
            'source_id'     => $sourceId,
            'source_url'    => $sourceId !== '' ? 'https://foursquare.com/v/' . $sourceId : null,
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
     * Parse Foursquare regular hours into 7-day array.
     *
     * Foursquare format:
     * [{"day": 1, "open": "0900", "close": "2200"}, ...]
     * day: 1=Monday … 7=Sunday
     */
    private function parseFoursquareHours(array $regular): array
    {
        $dayNames = [1=>'monday',2=>'tuesday',3=>'wednesday',4=>'thursday',5=>'friday',6=>'saturday',7=>'sunday'];
        $map      = [];

        foreach ($regular as $entry) {
            $dayNum = (int)($entry['day'] ?? 0);
            if (!isset($dayNames[$dayNum])) {
                continue;
            }
            $day     = $dayNames[$dayNum];
            $opensAt  = $this->formatFsqTime($entry['open']  ?? '');
            $closesAt = $this->formatFsqTime($entry['close'] ?? '');
            $map[$day] = ['opens_at' => $opensAt, 'closes_at' => $closesAt, 'is_closed' => false];
        }

        $result = [];
        foreach ($dayNames as $d) {
            $result[] = [
                'day_of_week' => $d,
                'opens_at'    => $map[$d]['opens_at']  ?? null,
                'closes_at'   => $map[$d]['closes_at'] ?? null,
                'is_closed'   => !isset($map[$d]),
            ];
        }
        return $result;
    }

    /** Convert "0930" → "09:30" */
    private function formatFsqTime(string $t): ?string
    {
        $t = trim($t);
        if (strlen($t) === 4 && ctype_digit($t)) {
            return substr($t, 0, 2) . ':' . substr($t, 2, 2);
        }
        return null;
    }

    private function apiKey(): string
    {
        try {
            return api_env('MCI_FOURSQUARE_API_KEY');
        } catch (Throwable) {
            return '';
        }
    }

    private function httpGet(string $url, string $apiKey): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: ' . $apiKey,
                'Accept: application/json',
                'User-Agent: MyCityInfo-Scraper/1.0',
            ],
        ]);
        $result = curl_exec($ch);
        $err    = curl_error($ch);
        curl_close($ch);
        return ($err !== '' || $result === false) ? null : (string)$result;
    }
}
