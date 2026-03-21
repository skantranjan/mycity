<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function api_jwt_base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function api_jwt_base64url_decode(string $data): string
{
    $pad = strlen($data) % 4;
    if ($pad) {
        $data .= str_repeat('=', 4 - $pad);
    }
    $decoded = base64_decode(strtr($data, '-_', '+/'), true);
    return $decoded === false ? '' : $decoded;
}

function api_jwt_sign(array $payload): string
{
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $secret = api_jwt_secret();

    $segments = [];
    $segments[] = api_jwt_base64url_encode(json_encode($header, JSON_UNESCAPED_SLASHES));
    $segments[] = api_jwt_base64url_encode(json_encode($payload, JSON_UNESCAPED_SLASHES));

    $signingInput = implode('.', $segments);
    $signature = hash_hmac('sha256', $signingInput, $secret, true);
    $segments[] = api_jwt_base64url_encode($signature);

    return implode('.', $segments);
}

function api_jwt_verify(string $jwt): ?array
{
    $secret = api_jwt_secret();
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        return null;
    }

    [$h64, $p64, $s64] = $parts;
    $headerJson = api_jwt_base64url_decode($h64);
    $payloadJson = api_jwt_base64url_decode($p64);
    $sig = api_jwt_base64url_decode($s64);

    if ($headerJson === '' || $payloadJson === '' || $sig === '') {
        return null;
    }

    $header = json_decode($headerJson, true);
    $payload = json_decode($payloadJson, true);
    if (!is_array($header) || !is_array($payload)) {
        return null;
    }

    if (($header['alg'] ?? '') !== 'HS256') {
        return null;
    }

    $signingInput = $h64 . '.' . $p64;
    $expected = hash_hmac('sha256', $signingInput, $secret, true);
    if (!hash_equals($expected, $sig)) {
        return null;
    }

    // Exp check (optional).
    if (isset($payload['exp'])) {
        $exp = (int)$payload['exp'];
        if ($exp > 0 && time() > $exp) {
            return null;
        }
    }

    return $payload;
}

