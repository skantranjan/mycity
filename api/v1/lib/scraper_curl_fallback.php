<?php
declare(strict_types=1);

require_once __DIR__ . '/scraper_adapter_interface.php';

/**
 * cURL / HTML scraping fallback adapter.
 *
 * Fetches MCI_SCRAPER_CURL_TARGET URL, parses structured HTML using DOMDocument.
 * Expected HTML schema:
 *
 *   <div class="mci-business" data-source-id="...">
 *     <span class="mci-name">Business Name</span>
 *     <span class="mci-phone">+91 98765 43210</span>
 *     <span class="mci-website">https://example.com</span>
 *     <span class="mci-address">123 Main St, Pune, Maharashtra 411001</span>
 *     <span class="mci-city">Pune</span>
 *     <span class="mci-category">IT Services</span>
 *     <span class="mci-lat">18.5204</span>
 *     <span class="mci-lon">73.8567</span>
 *   </div>
 *
 * If target HTML uses a different schema, customise extractField() and extractItems().
 *
 * Set MCI_SCRAPER_CURL_ENABLED=true and MCI_SCRAPER_CURL_TARGET=<url> in .env
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
        return null; // No external API limit — cURL is unlimited
    }

    public function alertThreshold(): int
    {
        return 80; // Not used (monthlyLimit is null)
    }

    public function search(array $params): array
    {
        $target = $this->targetUrl();
        if ($target === '') {
            return [];
        }

        $q    = trim((string)($params['q'] ?? ''));
        $city = trim((string)($params['city'] ?? ''));

        // Append query params to target URL if present
        $url = $target;
        $qp  = array_filter(['q' => $q, 'city' => $city]);
        if (!empty($qp)) {
            $sep  = str_contains($url, '?') ? '&' : '?';
            $url .= $sep . http_build_query($qp);
        }

        $html = $this->httpGet($url);
        if ($html === null) {
            return [];
        }

        return $this->parseHtml($html, $city);
    }

    private function parseHtml(string $html, string $cityHint): array
    {
        // Suppress XML/HTML parse warnings
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
            /** @var DOMElement $node */
            $record = $this->normaliseNode($node, $xpath, $cityHint);
            if ($record !== null) {
                $results[] = $record;
            }
        }
        return $results;
    }

    private function normaliseNode(DOMElement $node, DOMXPath $xpath, string $cityHint): ?array
    {
        $name = $this->extractField($node, $xpath, 'mci-name');
        if ($name === null || $name === '') {
            return null;
        }

        $phone        = $this->extractField($node, $xpath, 'mci-phone');
        $website      = $this->extractField($node, $xpath, 'mci-website');
        $address      = $this->extractField($node, $xpath, 'mci-address');
        $city         = $this->extractField($node, $xpath, 'mci-city') ?? $cityHint ?: null;
        $categoryHint = $this->extractField($node, $xpath, 'mci-category');
        $latRaw       = $this->extractField($node, $xpath, 'mci-lat');
        $lonRaw       = $this->extractField($node, $xpath, 'mci-lon');
        $state        = $this->extractField($node, $xpath, 'mci-state');
        $pincode      = $this->extractField($node, $xpath, 'mci-pincode');
        $line1        = $this->extractField($node, $xpath, 'mci-address-line1');

        $lat = ($latRaw !== null && $latRaw !== '') ? (float)$latRaw : null;
        $lon = ($lonRaw !== null && $lonRaw !== '') ? (float)$lonRaw : null;

        // Source ID: use data-source-id attribute, or md5 of name|city|address
        $sourceId = trim((string)$node->getAttribute('data-source-id'));
        if ($sourceId === '') {
            $sourceId = md5($name . '|' . ($city ?? '') . '|' . ($address ?? ''));
        }

        $typesRaw     = $categoryHint !== null ? [$categoryHint] : [];
        $fullAddress  = $address ?? implode(', ', array_filter([$line1, $city, $state, $pincode, 'India']));

        $payload = [
            'source'      => 'curl_scrape',
            'data_source' => 'scrape_html',
            'group' => [
                'name'               => $name,
                'tagline'            => null,
                'description'        => $this->extractField($node, $xpath, 'mci-description'),
                'established_year'   => null,
                'website_url'        => $website,
                'email'              => $this->extractField($node, $xpath, 'mci-email'),
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
                'whatsapp_number' => $this->extractField($node, $xpath, 'mci-whatsapp'),
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

    /** Extract text content of first descendant element with the given CSS class. */
    private function extractField(DOMElement $node, DOMXPath $xpath, string $class): ?string
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

    private function httpGet(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; MyCityInfo-Scraper/1.0)',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml',
                'Accept-Language: en-IN,en;q=0.9',
            ],
        ]);
        $result = curl_exec($ch);
        $err    = curl_error($ch);
        curl_close($ch);
        return ($err !== '' || $result === false) ? null : (string)$result;
    }
}
