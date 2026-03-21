<?php
declare(strict_types=1);

const API_JWT_COOKIE = 'mci_api_token';

function api_read_auth_token_from_cookie(): ?string
{
    $cookie = $_COOKIE[API_JWT_COOKIE] ?? null;
    if (!is_string($cookie) || trim($cookie) === '') {
        return null;
    }
    return $cookie;
}

function api_write_auth_token_cookie(string $jwt, int $expTimestamp): void
{
    // 15m expiry default is driven by JWT exp, cookie expiry follows it.
    $maxAge = max(0, $expTimestamp - time());

    setcookie(
        API_JWT_COOKIE,
        $jwt,
        [
            'expires' => time() + $maxAge,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );
}

function api_clear_auth_token_cookie(): void
{
    setcookie(
        API_JWT_COOKIE,
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );
}

