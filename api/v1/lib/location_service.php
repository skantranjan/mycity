<?php
declare(strict_types=1);

/**
 * Upsert a (country, state, city) combination into mci_locations.
 *
 * Called after a successful business branch insert (outside the transaction).
 * Failures are silently swallowed — this must never block listing creation.
 *
 * @param PDO    $pdo
 * @param string $country  — max 100 chars, defaults to 'India' if blank
 * @param string $state    — max 100 chars, empty string means "no state"
 * @param string $city     — max 100 chars, must be non-empty to be stored
 */
function api_locations_upsert(PDO $pdo, string $country, string $state, string $city): void
{
    $city    = mb_substr(trim($city),    0, 100);
    $state   = mb_substr(trim($state),   0, 100);
    $country = mb_substr(trim($country), 0, 100);

    if ($city === '') {
        return;
    }
    if ($country === '') {
        $country = 'India';
    }

    $pdo->prepare(
        'INSERT INTO mci_locations (country, state, city)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE country = country'
    )->execute([$country, $state, $city]);
}
