<?php

declare(strict_types=1);

/**
 * Comma-separated trusted proxy IPs (e.g. load balancer). When REMOTE_ADDR matches,
 * the first valid hop in X-Forwarded-For is used; otherwise XFF is ignored (spoof-safe default).
 *
 * Env: MCI_TRUSTED_PROXY_IPS
 */
function api_trusted_proxy_ips(): array
{
    $raw = getenv('MCI_TRUSTED_PROXY_IPS');
    if (!is_string($raw) || trim($raw) === '') {
        $raw = $_ENV['MCI_TRUSTED_PROXY_IPS'] ?? $_SERVER['MCI_TRUSTED_PROXY_IPS'] ?? '';
    }
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', explode(',', $raw))));
}

/**
 * Best-effort client IP (IPv4/IPv6). Truncated to 45 chars for VARCHAR(45) columns.
 */
function api_client_ip(): string
{
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    $remote = is_string($remote) ? $remote : '';

    $trusted = api_trusted_proxy_ips();
    if ($trusted !== [] && $remote !== '' && in_array($remote, $trusted, true)) {
        $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if (is_string($xff) && $xff !== '') {
            $parts = array_map('trim', explode(',', $xff));
            $first = $parts[0] ?? '';
            if ($first !== '' && filter_var($first, FILTER_VALIDATE_IP)) {
                return mb_substr($first, 0, 45);
            }
        }
    }

    if ($remote !== '' && filter_var($remote, FILTER_VALIDATE_IP)) {
        return mb_substr($remote, 0, 45);
    }

    return mb_substr($remote, 0, 45);
}
