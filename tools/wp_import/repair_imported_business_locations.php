<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

require_once dirname(__DIR__, 2) . '/includes/mci_load_env.php';
require_once __DIR__ . '/gaddress_parse.php';
mci_load_dotenv();

function env_req(string $key): string
{
    $value = getenv($key);
    if (!is_string($value) || trim($value) === '') {
        throw new RuntimeException("Missing required env var: {$key}");
    }
    return trim($value);
}

function env_opt(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if (!is_string($value) || trim($value) === '') {
        return $default;
    }
    return trim($value);
}

function pdo_mysql(string $host, string $db, string $user, string $pass, ?string $port = null): PDO
{
    $portPart = ($port !== null && $port !== '') ? ';port=' . (int)$port : '';
    return new PDO(
        "mysql:host={$host}{$portPart};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

function detect_wp_prefix(PDO $wp): string
{
    $configured = env_opt('WP_TABLE_PREFIX');
    if ($configured !== null) {
        return $configured;
    }
    $rows = $wp->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);
    foreach ($rows as $row) {
        $table = (string)($row[0] ?? '');
        if (str_ends_with($table, 'posts')) {
            return substr($table, 0, -5);
        }
    }
    throw new RuntimeException('Could not detect WP table prefix.');
}

function first_non_empty(array $meta, array $keys): ?string
{
    foreach ($keys as $key) {
        if (!array_key_exists($key, $meta)) {
            continue;
        }
        $value = trim((string)$meta[$key]);
        if ($value !== '') {
            return $value;
        }
    }
    return null;
}

function is_missing(?string $value, array $placeholders = []): bool
{
    $v = trim((string)$value);
    if ($v === '') {
        return true;
    }
    $vLower = strtolower($v);
    foreach ($placeholders as $p) {
        if ($vLower === strtolower($p)) {
            return true;
        }
    }
    return false;
}

try {
    $mci = pdo_mysql(
        env_req('MCI_DB_HOST'),
        env_req('MCI_DB_NAME'),
        env_req('MCI_DB_USER'),
        env_req('MCI_DB_PASS'),
        env_opt('MCI_DB_PORT')
    );
    $wp = pdo_mysql(
        env_req('WP_DB_HOST'),
        env_req('WP_DB_NAME'),
        env_req('WP_DB_USER'),
        env_req('WP_DB_PASS'),
        env_opt('WP_DB_PORT')
    );
    $wpPrefix = detect_wp_prefix($wp);

    $mapRows = $mci->query("
        SELECT source_id, target_id
        FROM mci_wp_import_map
        WHERE source_type = 'wp_post' AND target_type = 'mci_business_group'
    ")->fetchAll();

    $postIds = array_map(static fn(array $r): int => (int)$r['source_id'], $mapRows);
    if ($postIds === []) {
        echo "No mapped imported businesses found.\n";
        exit(0);
    }

    $metaByPostId = [];
    $chunks = array_chunk($postIds, 500);
    foreach ($chunks as $chunk) {
        $in = implode(',', array_fill(0, count($chunk), '?'));
        $stmt = $wp->prepare("SELECT post_id, meta_key, meta_value FROM {$wpPrefix}postmeta WHERE post_id IN ({$in})");
        $stmt->execute($chunk);
        foreach ($stmt->fetchAll() as $row) {
            $metaByPostId[(int)$row['post_id']][(string)$row['meta_key']] = (string)$row['meta_value'];
        }
    }

    $branchStmt = $mci->prepare("
        SELECT id, address_line1, city, state, country, pincode, phone_primary, website, latitude, longitude
        FROM mci_business_branches
        WHERE business_group_id = ?
        ORDER BY is_primary DESC, created_at ASC
        LIMIT 1
    ");
    $updateStmt = $mci->prepare("
        UPDATE mci_business_branches
        SET address_line1 = ?, city = ?, state = ?, country = ?, pincode = ?, phone_primary = ?, website = ?, latitude = ?, longitude = ?, updated_at = NOW(6)
        WHERE id = ?
    ");

    $checked = 0;
    $updated = 0;
    $skipNoPrimary = 0;
    foreach ($mapRows as $map) {
        $checked++;
        $postId = (int)$map['source_id'];
        $groupId = (string)$map['target_id'];
        $meta = $metaByPostId[$postId] ?? [];

        $branchStmt->execute([$groupId]);
        $branch = $branchStmt->fetch();
        if (!$branch) {
            $skipNoPrimary++;
            continue;
        }

        $lpRaw = trim((string)($meta['lp_listingpro_options'] ?? ''));
        $lp = is_array(@unserialize($lpRaw)) ? @unserialize($lpRaw) : [];
        if (!is_array($lp)) {
            $lp = [];
        }
        $parsed = mci_parse_location_from_gaddress((string)($lp['gAddress'] ?? ''));

        $address = first_non_empty($meta, ['address', '_address', 'business_address', 'street_address', 'address_line_1', 'address_line1', 'listing_address', 'location_address', '_location_address'])
            ?? (is_string($parsed['address']) ? $parsed['address'] : null);
        $city = first_non_empty($meta, ['city', '_city', 'business_city', 'town', 'location_city', '_location_city'])
            ?? (is_string($parsed['city']) ? $parsed['city'] : null);
        $state = first_non_empty($meta, ['state', '_state', 'business_state', 'province', 'region', 'location_state'])
            ?? (is_string($parsed['state']) ? $parsed['state'] : null);
        $country = first_non_empty($meta, ['country', '_country', 'business_country', 'location_country'])
            ?? (is_string($parsed['country']) ? $parsed['country'] : null);
        $pincode = first_non_empty($meta, ['pincode', 'postal_code', 'zip', '_zip', 'zipcode', 'zip_code', 'location_pincode'])
            ?? (is_string($parsed['pincode'] ?? null) && trim((string)$parsed['pincode']) !== '' ? trim((string)$parsed['pincode']) : null);
        $phone = first_non_empty($meta, ['phone', '_phone', 'mobile', 'contact_number', 'telephone', 'phone_number', '_phone_number'])
            ?? (trim((string)($lp['phone'] ?? '')) !== '' ? trim((string)$lp['phone']) : null);
        $website = first_non_empty($meta, ['website', '_website', 'url', 'business_website', 'company_website', 'contact_website'])
            ?? (trim((string)($lp['website'] ?? '')) !== '' ? trim((string)$lp['website']) : null);
        $lat = first_non_empty($meta, ['lat', 'latitude', '_latitude', 'geo_latitude', 'map_lat'])
            ?? (trim((string)($lp['latitude'] ?? '')) !== '' ? trim((string)$lp['latitude']) : null);
        $lng = first_non_empty($meta, ['lng', 'lon', 'longitude', '_longitude', 'geo_longitude', 'map_lng'])
            ?? (trim((string)($lp['longitude'] ?? '')) !== '' ? trim((string)$lp['longitude']) : null);

        $newAddress = is_missing((string)$branch['address_line1'], ['address not provided']) && $address !== null ? $address : (string)$branch['address_line1'];
        $newCity = is_missing((string)$branch['city'], ['unknown']) && $city !== null ? $city : (string)$branch['city'];
        $gAddrRaw = trim((string)($lp['gAddress'] ?? ''));
        $fillFromLpStructured = is_missing((string)$branch['city'], ['unknown'])
            && $city !== null
            && $gAddrRaw !== '';
        $newState = (string)$branch['state'];
        if ($fillFromLpStructured && $state !== null && trim($state) !== '') {
            $newState = $state;
        } elseif (is_missing($newState) && $state !== null) {
            $newState = $state;
        }
        $newCountry = (string)$branch['country'];
        if ($fillFromLpStructured && $country !== null && trim($country) !== '') {
            $newCountry = $country;
        } elseif (is_missing($newCountry) && $country !== null) {
            $newCountry = $country;
        }
        if (trim($newCountry) === '') {
            $newCountry = 'India';
        }
        if (preg_match('/^(usa|united states)$/i', $gAddrRaw) === 1
            && strtolower(trim((string)$branch['country'])) === 'india') {
            $newCountry = 'United States';
        }
        $newPincode = (string)$branch['pincode'];
        if ($fillFromLpStructured && $pincode !== null && trim($pincode) !== '') {
            $newPincode = $pincode;
        } elseif (is_missing($newPincode) && $pincode !== null) {
            $newPincode = $pincode;
        }
        $newPhone = is_missing((string)$branch['phone_primary']) && $phone !== null ? $phone : (string)$branch['phone_primary'];
        $newWebsite = is_missing((string)$branch['website']) && $website !== null ? $website : (string)$branch['website'];
        $newLat = (is_missing((string)$branch['latitude']) && $lat !== null && is_numeric($lat)) ? (string)$lat : (string)$branch['latitude'];
        $newLng = (is_missing((string)$branch['longitude']) && $lng !== null && is_numeric($lng)) ? (string)$lng : (string)$branch['longitude'];

        $changed =
            $newAddress !== (string)$branch['address_line1'] ||
            $newCity !== (string)$branch['city'] ||
            $newState !== (string)$branch['state'] ||
            $newCountry !== (string)$branch['country'] ||
            $newPincode !== (string)$branch['pincode'] ||
            $newPhone !== (string)$branch['phone_primary'] ||
            $newWebsite !== (string)$branch['website'] ||
            $newLat !== (string)$branch['latitude'] ||
            $newLng !== (string)$branch['longitude'];

        if (!$changed) {
            continue;
        }

        $updateStmt->execute([
            $newAddress !== '' ? $newAddress : null,
            $newCity !== '' ? $newCity : null,
            $newState !== '' ? $newState : null,
            $newCountry !== '' ? $newCountry : null,
            $newPincode !== '' ? $newPincode : null,
            $newPhone !== '' ? $newPhone : null,
            $newWebsite !== '' ? $newWebsite : null,
            $newLat !== '' ? $newLat : null,
            $newLng !== '' ? $newLng : null,
            (string)$branch['id'],
        ]);
        $updated++;
    }

    echo "Location/contact remediation complete.\n";
    echo "- Checked imported businesses: {$checked}\n";
    echo "- Updated primary branches: {$updated}\n";
    echo "- Skipped (no primary branch): {$skipNoPrimary}\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Repair failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

