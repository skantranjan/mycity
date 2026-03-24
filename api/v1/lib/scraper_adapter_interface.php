<?php
declare(strict_types=1);

/**
 * ScraperAdapter — interface every data-source adapter must implement.
 *
 * search() returns an array of normalised business records, each shaped as:
 * [
 *   'source'        => string  — adapter name: 'osm', 'tomtom', 'here', 'google_places', 'curl_scrape'
 *   'source_id'     => string  — external unique ID for dedup (md5 hash for curl/osm-fallback)
 *   'source_url'    => ?string — URL used or listing URL on the source
 *   'name'          => string
 *   'category_hint' => ?string — raw category/type string from source
 *   'types_raw'     => array   — all source type tags (for tag auto-matching)
 *   'city'          => ?string
 *   'phone'         => ?string
 *   'website'       => ?string
 *   'address'       => ?string — full address string
 *   'latitude'      => ?float
 *   'longitude'     => ?float
 *   'payload_json'  => string  — JSON-encoded import payload (see scraper_service.php)
 * ]
 */
interface ScraperAdapter
{
    /**
     * Search for businesses matching the given params.
     *
     * Supported params (all optional except at least one of q/city/category):
     *   'q'        => string  — keyword query (e.g. "IT companies", "restaurants")
     *   'city'     => string  — city/area name (e.g. "Pune", "Mumbai")
     *   'category' => string  — category hint (e.g. "restaurant", "IT")
     *
     * Returns array of normalised records (see interface docblock).
     */
    public function search(array $params): array;

    /**
     * Returns the source identifier string stored in mci_scraped_businesses.source.
     * e.g. 'osm', 'tomtom', 'here', 'google_places', 'curl_scrape'
     */
    public function sourceName(): string;

    /**
     * Returns true if this adapter is ready to use (key configured, flag enabled, etc.).
     */
    public function isAvailable(): bool;

    /**
     * Monthly call limit for this source. Returns null if unlimited (e.g. OSM).
     */
    public function monthlyLimit(): ?int;

    /**
     * Alert threshold as a percentage (0–100). Defaults to 80.
     * Warning shown when (call_count / monthly_limit * 100) >= this value.
     */
    public function alertThreshold(): int;
}
