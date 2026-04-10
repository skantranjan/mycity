<?php
declare(strict_types=1);

/**
 * Best-effort parse of ListingPro gAddress into structured fields.
 * Handles common US/IN/SG/ZA shapes where comma-only splitting fails.
 *
 * @return array{address: ?string, city: ?string, state: ?string, country: ?string, pincode: ?string}
 */
function mci_parse_location_from_gaddress(?string $gAddress): array
{
    $addr = trim(html_entity_decode((string)$gAddress, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($addr === '') {
        return ['address' => null, 'city' => null, 'state' => null, 'country' => null, 'pincode' => null];
    }
    // Fuse "Street.Middelburg" → space so downstream tokens split correctly
    $addr = preg_replace('/\s*\.\s*/', ' ', $addr) ?? $addr;
    $addr = preg_replace('/\s+/', ' ', trim($addr));

    $addrUpper = strtoupper($addr);
    if ($addrUpper === 'USA' || $addrUpper === 'UNITED STATES' || $addrUpper === 'UNITED STATES OF AMERICA') {
        return [
            'address' => $addr,
            'city' => null,
            'state' => null,
            'country' => 'United States',
            'pincode' => null,
        ];
    }

    // --- India: ", City - 110029" (6-digit PIN at end)
    if (preg_match('/,\s*([^,]+?)\s*-\s*(\d{6})\s*$/u', $addr, $m)) {
        $cityPart = trim($m[1]);
        if ($cityPart !== '' && !preg_match('/^\d/', $cityPart)) {
            return [
                'address' => $addr,
                'city' => $cityPart,
                'state' => null,
                'country' => 'India',
                'pincode' => $m[2],
            ];
        }
    }

    // --- South Africa: "... Middelburg Eastern Cape South Africa 1234" (city = word before province)
    if (preg_match(
        '/\b([A-Za-z][A-Za-z\-]+)\s+(Eastern Cape|Western Cape|Gauteng|KwaZulu-Natal|Free State|Mpumalanga|Limpopo|North West|Northern Cape)\s+South Africa\s+(\d{4})\s*$/iu',
        $addr,
        $m
    )) {
        return [
            'address' => $addr,
            'city' => trim($m[1]),
            'state' => $m[2],
            'country' => 'South Africa',
            'pincode' => $m[3],
        ];
    }

    // --- Singapore + 6-digit postal
    if (preg_match('/\bSingapore\s+(\d{6})\s*$/iu', $addr, $m)) {
        return [
            'address' => $addr,
            'city' => 'Singapore',
            'state' => null,
            'country' => 'Singapore',
            'pincode' => $m[1],
        ];
    }

    // --- US: trailing ", ST 12345" or ", ST 12345 USA"
    if (preg_match('/,\s*([A-Z]{2})\s+(\d{5}(?:-\d{4})?)\s*(?:USA|United States)?\s*$/iu', $addr, $m)) {
        $before = trim(substr($addr, 0, (int)strpos($addr, ',')));
        $cityToken = mci_guess_trailing_city_token($before);
        if ($cityToken !== null) {
            return [
                'address' => $addr,
                'city' => $cityToken,
                'state' => strtoupper($m[1]),
                'country' => 'United States',
                'pincode' => $m[2],
            ];
        }
    }

    // --- US: "… Palm City FL 34990" (no comma before state; anchor at end)
    if (preg_match('/\s([A-Za-z][A-Za-z]+(?:\s+[A-Za-z][A-Za-z]+)?)\s+([A-Z]{2})\s+(\d{5}(?:-\d{4})?)\s*$/u', $addr, $m)) {
        $cityPart = trim($m[1]);
        if (!preg_match('/\d/', $cityPart) && strlen($cityPart) >= 3) {
            return [
                'address' => $addr,
                'city' => $cityPart,
                'state' => strtoupper($m[2]),
                'country' => 'United States',
                'pincode' => $m[3],
            ];
        }
    }

    // --- US: ", StateName 12345" or ", statename 12345 unitedstates"
    $lastComma = strrpos($addr, ',');
    if ($lastComma !== false) {
        $left = trim(substr($addr, 0, $lastComma));
        $right = trim(substr($addr, $lastComma + 1));
        if (preg_match('/^([A-Za-z][A-Za-z .]+?)\s+(\d{5}(?:-\d{4})?)\s*(?:unitedstates|united states|usa)?\s*$/iu', $right, $rm)) {
            $stateName = trim($rm[1]);
            $zip = $rm[2];
            $cityToken = mci_guess_trailing_city_token($left);
            if ($cityToken !== null && strlen($stateName) >= 3 && strlen($stateName) <= 28) {
                return [
                    'address' => $addr,
                    'city' => $cityToken,
                    'state' => $stateName,
                    'country' => 'United States',
                    'pincode' => $zip,
                ];
            }
        }
    }

    // --- Fallback: comma segments (original behaviour, extended with pincode guess)
    return mci_parse_location_from_gaddress_fallback($addr);
}

/**
 * @return array{address: ?string, city: ?string, state: ?string, country: ?string, pincode: ?string}
 */
function mci_parse_location_from_gaddress_fallback(string $addr): array
{
    $parts = array_values(array_filter(array_map('trim', explode(',', $addr)), static fn(string $v): bool => $v !== ''));
    $count = count($parts);
    $country = $count >= 1 ? $parts[$count - 1] : null;
    $state = $count >= 2 ? $parts[$count - 2] : null;
    $city = $count >= 3 ? $parts[$count - 3] : null;
    $pincode = null;
    if ($country !== null && preg_match('/\b(\d{5}(?:-\d{4})?|\d{6})\b/', $country, $zm)) {
        $pincode = $zm[1];
    }

    return [
        'address' => $addr,
        'city' => $city,
        'state' => $state,
        'country' => $country,
        'pincode' => $pincode,
    ];
}

function mci_guess_trailing_city_token(string $beforeComma): ?string
{
    $beforeComma = preg_replace('/\s+/', ' ', trim($beforeComma));
    if ($beforeComma === '') {
        return null;
    }
    $tokens = preg_split('/\s+/', $beforeComma) ?: [];
    $skip = ['suite', 'ste', 'unit', 'floor', 'fl', '#', 'bldg', 'building'];
    $streetHints = ['street', 'st', 'avenue', 'ave', 'road', 'rd', 'drive', 'dr', 'boulevard', 'blvd', 'lane', 'ln', 'court', 'ct', 'parkway', 'pkwy', 'way', 'circle', 'cir', 'highway', 'hwy', 'place', 'pl'];
    $n = count($tokens);
    $j = $n - 1;
    while ($j >= 0) {
        $raw = (string)($tokens[$j] ?? '');
        $t = preg_replace('/[^A-Za-z\-]/', '', $raw) ?? '';
        if ($t === '') {
            $j--;
            continue;
        }
        $low = strtolower($t);
        if (in_array($low, $skip, true)) {
            $j--;
            continue;
        }
        if (preg_match('/^\d+[A-Za-z]?$/', $t) === 1) {
            $j--;
            continue;
        }
        if (in_array($low, $streetHints, true)) {
            $j--;
            continue;
        }
        // One-word city (e.g. Aurora, Carlsbad) or two-word (Sheffield Village)
        $parts = [$raw];
        if ($j - 1 >= 0) {
            $rawPrev = (string)($tokens[$j - 1] ?? '');
            $prev = preg_replace('/[^A-Za-z\-]/', '', $rawPrev) ?? '';
            $lowPrev = strtolower($prev);
            $noSecondWord = ['east', 'west', 'north', 'south', 'northeast', 'northwest', 'southeast', 'southwest', 'first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth', 'ninth', 'tenth'];
            if (
                $prev !== ''
                && strlen($prev) > 1
                && !in_array($lowPrev, $skip, true)
                && !in_array($lowPrev, $streetHints, true)
                && !in_array($lowPrev, $noSecondWord, true)
                && preg_match('/^\d+[A-Za-z]?$/', $prev) !== 1
            ) {
                array_unshift($parts, $rawPrev);
            }
        }

        return trim(implode(' ', $parts));
    }

    return null;
}

/** City-only helper for second-pass heuristic (skip country/postal-like tail segments). */
function mci_city_from_gaddress_heuristic(string $g): ?string
{
    $p = mci_parse_location_from_gaddress($g);
    if (is_string($p['city']) && trim($p['city']) !== '') {
        return trim($p['city']);
    }
    $parts = array_values(array_filter(array_map('trim', explode(',', $g)), static fn(string $x): bool => $x !== ''));
    if ($parts === []) {
        return null;
    }
    $badEnd = static function (string $x): bool {
        $x = strtolower(trim($x));
        if ($x === '') {
            return true;
        }
        $bad = ['india', 'uk', 'usa', 'united states', 'united kingdom', 'australia', 'canada', 'singapore', 'south africa'];
        foreach ($bad as $b) {
            if ($x === $b) {
                return true;
            }
        }
        if (preg_match('/^[0-9\-\s]+$/', $x) === 1) {
            return true;
        }
        if (preg_match('/\b\d{5,6}\b/', $x) === 1) {
            return true;
        }

        return false;
    };
    for ($i = count($parts) - 1; $i >= 0; $i--) {
        $seg = $parts[$i];
        if ($badEnd($seg)) {
            continue;
        }
        if (mb_strlen($seg) > 45) {
            continue;
        }

        return $seg;
    }

    return null;
}
