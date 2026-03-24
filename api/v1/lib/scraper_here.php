<?php
declare(strict_types=1);

require_once __DIR__ . '/scraper_adapter_interface.php';

/**
 * HERE Places Discover API adapter.
 *
 * Free tier: 250,000 transactions/month. No credit card required.
 * Sign up at: https://developer.here.com
 * Set MCI_SCRAPER_HERE_ENABLED=true and MCI_HERE_API_KEY in .env
 */
class ScraperHere implements ScraperAdapter
{
    public function sourceName(): string
    {
        return 'here';
    }

    public function isAvailable(): bool
    {
        try {
            return api_env_flag('MCI_SCRAPER_HERE_ENABLED')
                && api_env('MCI_HERE_API_KEY') !== '';
        } catch (Throwable) {
            return false;
        }
    }

    public function monthlyLimit(): ?int
    {
        try {
            $v = (int) api_env('MCI_HERE_MONTHLY_LIMIT');
            return $v > 0 ? $v : 250000;
        } catch (Throwable) {
            return 250000;
        }
    }

    public function alertThreshold(): int
    {
        try {
            $v = (int) api_env('MCI_HERE_ALERT_THRESHOLD');
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

        // HERE Discover API — text search with India bounding box
        // India bbox: approx lat 6–37, lon 68–98
        $url = 'https://discover.search.hereapi.com/v1/discover'
             . '?q=' . rawurlencode($query)
             . '&in=countryCode:IND'
             . '&limit=20'
             . '&apiKey=' . rawurlencode($apiKey);

        // If city provided, use geocoded centre for better relevance
        // (simple heuristic: add city to query is already done above)

        $raw = $this->httpGet($url);
        if ($raw === null) {
            return [];
        }

        $json = json_decode($raw, true);
        if (!is_array($json) || !isset($json['items'])) {
            return [];
        }

        $results = [];
        foreach ($json['items'] as $item) {
            $record = $this->normalise($item, $city);
            if ($record !== null) {
                $results[] = $record;
            }
        }
        return $results;
    }

    private function normalise(array $item, string $city): ?array
    {
        $name = trim((string)($item['title'] ?? ''));
        if ($name === '') {
            return null;
        }

        $address  = $item['address'] ?? [];
        $pos      = $item['position'] ?? [];
        $contacts = $item['contacts'][0] ?? [];

        $sourceId = (string)($item['id'] ?? '');
        $lat      = isset($pos['lat']) ? (float)$pos['lat'] : null;
        $lon      = isset($pos['lng']) ? (float)$pos['lng'] : null;

        // Phone
        $phone = null;
        if (!empty($contacts['phone'])) {
            $phone = trim((string)($contacts['phone'][0]['value'] ?? '')) ?: null;
        }

        // Website
        $website = null;
        if (!empty($contacts['www'])) {
            $website = trim((string)($contacts['www'][0]['value'] ?? '')) ?: null;
        }

        // Address fields
        $line1    = trim((string)($address['street'] ?? ''));
        $line1    = $line1 !== '' ? $line1 : null;
        $addrCity = trim((string)($address['city'] ?? $address['district'] ?? '')) ?: $city ?: null;
        $state    = trim((string)($address['state'] ?? '')) ?: null;
        $pincode  = trim((string)($address['postalCode'] ?? '')) ?: null;

        $fullAddress = implode(', ', array_filter([$line1, $addrCity, $state, $pincode, 'India']));

        // Categories
        $categoryHint = null;
        $typesRaw     = [];
        if (!empty($item['categories'])) {
            $categoryHint = (string)($item['categories'][0]['name'] ?? $item['categories'][0]['id'] ?? '');
            foreach ($item['categories'] as $cat) {
                $label = trim((string)($cat['name'] ?? $cat['id'] ?? ''));
                if ($label !== '') {
                    $typesRaw[] = $label;
                }
            }
        }

        // Opening hours
        $hoursArray = [];
        $hoursRaw   = null;
        if (!empty($item['openingHours'])) {
            $oh = $item['openingHours'][0] ?? [];
            $hoursRaw = implode('; ', $oh['text'] ?? []) ?: null;
            // HERE provides structured hours as isOpen/structured — parse if available
            if (!empty($oh['structured'])) {
                $hoursArray = $this->parseHereHours($oh['structured']);
            }
        }

        $payload = [
            'source'      => 'here',
            'data_source' => 'scrape_here',
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
            'hours'        => $hoursArray,
            'hours_raw'    => $hoursRaw,
            'social_links' => [],
        ];

        return [
            'source'        => 'here',
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

    /**
     * Parse HERE structured opening hours into the 7-day array format.
     *
     * HERE structured format:
     * [{"start":"T080000","duration":"PT10H","recurrence":"FREQ:DAILY;BYDAY:MO,TU,WE,TH,FR"}, ...]
     */
    private function parseHereHours(array $structured): array
    {
        $dayMap = [
            'MO' => 'monday', 'TU' => 'tuesday', 'WE' => 'wednesday',
            'TH' => 'thursday', 'FR' => 'friday', 'SA' => 'saturday', 'SU' => 'sunday',
        ];

        $days = ['monday' => null, 'tuesday' => null, 'wednesday' => null,
                 'thursday' => null, 'friday' => null, 'saturday' => null, 'sunday' => null];

        foreach ($structured as $entry) {
            $start    = (string)($entry['start'] ?? '');    // e.g. T080000
            $duration = (string)($entry['duration'] ?? ''); // e.g. PT10H or PT10H30M
            $recur    = (string)($entry['recurrence'] ?? '');

            // Parse start time
            if (!preg_match('/^T(\d{2})(\d{2})/', $start, $sm)) {
                continue;
            }
            $opensH = (int)$sm[1];
            $opensM = (int)$sm[2];
            $opensAt = sprintf('%02d:%02d', $opensH, $opensM);

            // Parse duration (ISO 8601)
            $hours = 0;
            $mins  = 0;
            if (preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?/', $duration, $dm)) {
                $hours = (int)($dm[1] ?? 0);
                $mins  = (int)($dm[2] ?? 0);
            }
            $totalMins  = $opensH * 60 + $opensM + $hours * 60 + $mins;
            $closesAt   = sprintf('%02d:%02d', intdiv($totalMins, 60) % 24, $totalMins % 60);

            // Which days?
            $byDay = [];
            if (preg_match('/BYDAY:([A-Z,]+)/', $recur, $bm)) {
                $byDay = explode(',', $bm[1]);
            } elseif (str_contains($recur, 'DAILY')) {
                $byDay = array_keys($dayMap);
            }

            foreach ($byDay as $abbr) {
                $abbr = strtoupper(trim($abbr));
                if (isset($dayMap[$abbr])) {
                    $days[$dayMap[$abbr]] = ['opens_at' => $opensAt, 'closes_at' => $closesAt, 'is_closed' => false];
                }
            }
        }

        // Build 7-element result
        $result = [];
        foreach ($days as $dayName => $info) {
            $result[] = [
                'day_of_week' => $dayName,
                'opens_at'    => $info['opens_at'] ?? null,
                'closes_at'   => $info['closes_at'] ?? null,
                'is_closed'   => $info === null ? true : $info['is_closed'],
            ];
        }
        return $result;
    }

    private function apiKey(): string
    {
        try {
            return api_env('MCI_HERE_API_KEY');
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
