<?php

declare(strict_types=1);

/**
 * Great-circle distance between two WGS84 points (kilometres).
 */
function mci_distance_km(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $earthKm = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(max(0.0, 1.0 - $a)));

    return round($earthKm * $c, 3);
}
