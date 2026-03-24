<?php
declare(strict_types=1);

require_once __DIR__ . '/scraper_adapter_interface.php';

/**
 * TomTom Places API adapter.
 *
 * Free tier: ~2,500 non-tile requests/day (~75,000/month). No credit card required.
 * Sign up at: https://developer.tomtom.com
 * Set MCI_SCRAPER_TOMTOM_ENABLED=true and MCI_TOMTOM_API_KEY in .env
 */
class ScraperTomTom implements ScraperAdapter
{
    public function sourceName(): string
    {
        return 'tomtom';
    }

    public function isAvailable(): bool
    {
        try {
            return api_env_flag('MCI_SCRAPER_TOMTOM_ENABLED')
                && api_env('MCI_TOMTOM_API_KEY') !== '';
        } catch (Throwable) {
            return false;
        }
    }

    public function monthlyLimit(): ?int
    {
        try {
            $v = (int) api_env('MCI_TOMTOM_MONTHLY_LIMIT');
            return $v > 0 ? $v : 75000;
        } catch (Throwable) {
            return 75000;
        }
    }

    public function alertThreshold(): int
    {
        try {
            $v = (int) api_env('MCI_TOMTOM_ALERT_THRESHOLD');
            return ($v > 0 && $v <= 100) ? $v : 80;
        } catch (Throwable) {
            return 80;
        }
    }

    public function search(array $params): array
    {
        $q    = trim((string)($params['q'] ?? ''));
        $city = trim((string)($params['city'] ?? ''));

        $query = trim($q . ($city !== '' ? ' ' . $city : ''));
        if ($query === '') {
            return [];
        }

        $apiKey = $this->apiKey();
        if ($apiKey === '') {
            return [];
        }

        $url = 'https://api.tomtom.com/search/2/search/' . rawurlencode($query) . '.json'
             . '?key=' . rawurlencode($apiKey)
             . '&limit=20'
             . '&countrySet=IN'
             . '&typeahead=false'
             . '&categorySet='; // leave blank for broad results

        $raw = $this->httpGet($url);
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
        $poi     = $item['poi'] ?? [];
        $address = $item['address'] ?? [];
        $pos     = $item['position'] ?? [];

        $name = trim((string)($poi['name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $sourceId  = (string)($item['id'] ?? '');
        $lat       = isset($pos['lat']) ? (float)$pos['lat'] : null;
        $lon       = isset($pos['lon']) ? (float)$pos['lon'] : null;
        $phone     = trim((string)($poi['phone'] ?? '')) ?: null;
        $website   = trim((string)($poi['url']   ?? '')) ?: null;

        $line1   = trim((string)($address['streetName'] ?? '') . ' ' . ($address['streetNumber'] ?? ''));
        $line1   = trim($line1) ?: null;
        $addrCity = trim((string)($address['municipality'] ?? $address['localName'] ?? '')) ?: $city ?: null;
        $state   = trim((string)($address['countrySubdivision'] ?? '')) ?: null;
        $pincode = trim((string)($address['extendedPostalCode'] ?? $address['postalCode'] ?? '')) ?: null;

        $fullAddress = implode(', ', array_filter([$line1, $addrCity, $state, $pincode, 'India']));

        $categoryHint = null;
        $typesRaw     = [];
        if (!empty($poi['categories'])) {
            $categoryHint = (string)$poi['categories'][0];
            $typesRaw     = array_map('strval', $poi['categories']);
        }

        $payload = [
            'source'      => 'tomtom',
            'data_source' => 'scrape_tomtom',
            'group' => [
                'name'               => $name,
                'tagline'            => null,
                'description'        => null,
                'established_year'   => null,
                'website_url'        => $website,
                'email'              => null,
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
            'hours'        => [],
            'hours_raw'    => null,
            'social_links' => [],
        ];

        return [
            'source'        => 'tomtom',
            'source_id'     => $sourceId,
            'source_url'    => null,
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

    private function apiKey(): string
    {
        try {
            return api_env('MCI_TOMTOM_API_KEY');
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
