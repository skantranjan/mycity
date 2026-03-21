<?php
declare(strict_types=1);

function api_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function api_error(string $message, int $status = 400, array $extra = []): never
{
    $base = ['error' => $message] + $extra;
    api_json($base, $status);
}

