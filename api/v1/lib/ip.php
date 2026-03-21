<?php
declare(strict_types=1);

/**
 * Best-effort client IP (IPv4/IPv6). Truncated to 45 chars for VARCHAR(45) columns.
 */
function api_client_ip(): string
{
    $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if (is_string($xff) && $xff !== '') {
        $parts = array_map('trim', explode(',', $xff));
        $first = $parts[0] ?? '';
        if ($first !== '' && filter_var($first, FILTER_VALIDATE_IP)) {
            return mb_substr($first, 0, 45);
        }
    }
    $real = $_SERVER['REMOTE_ADDR'] ?? '';
    $ip = is_string($real) ? $real : '';
    if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
        return mb_substr($ip, 0, 45);
    }
    return mb_substr($ip, 0, 45);
}
