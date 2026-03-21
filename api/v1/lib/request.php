<?php
declare(strict_types=1);

function api_request_data(): array
{
    $raw = file_get_contents('php://input');
    if (!is_string($raw)) {
        return [];
    }
    $rawTrim = ltrim($raw);
    if ($rawTrim === '') {
        return $_POST ?: [];
    }

    $contentType = (string)($_SERVER['CONTENT_TYPE'] ?? '');
    $isJson = stripos($contentType, 'application/json') !== false;

    if ($isJson || ($rawTrim[0] ?? '') === '{' || ($rawTrim[0] ?? '') === '[') {
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    // Fallback for form posts
    $data = [];
    parse_str($raw, $data);
    if (!is_array($data) || $data === []) {
        return $_POST ?: [];
    }
    return $data;
}

function api_body_get_string(array $data, string $key): string
{
    $v = $data[$key] ?? '';
    if (is_string($v)) {
        return $v;
    }
    return (string)$v;
}

/** True for JSON true, 1, "1", "true", "on" (checkbox). */
function api_body_get_bool(array $data, string $key): bool
{
    $v = $data[$key] ?? null;
    if ($v === true || $v === 1 || $v === '1' || $v === 'true' || $v === 'on') {
        return true;
    }
    return false;
}

