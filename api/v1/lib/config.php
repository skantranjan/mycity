<?php
declare(strict_types=1);

/**
 * Env-only configuration (not committed).
 *
 * Loads project root `.env` if present (see `includes/mci_load_env.php`).
 *
 * Expected environment variables (safe defaults are NOT provided):
 * - MCI_DB_HOST, MCI_DB_NAME, MCI_DB_USER, MCI_DB_PASS
 * - MCI_JWT_SECRET
 */

require_once dirname(__DIR__, 3) . '/includes/mci_load_env.php';
mci_load_dotenv();

function api_env(string $key): string
{
    $v = getenv($key);
    if (!is_string($v) || trim($v) === '') {
        $v = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    }
    if (!is_string($v) || trim($v) === '') {
        throw new RuntimeException("Missing required env var: {$key}");
    }
    return (string)$v;
}

/** Non-empty trimmed value, or null if unset. */
function api_env_optional(string $key): ?string
{
    $v = getenv($key);
    if (!is_string($v) || trim($v) === '') {
        $v = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    }
    if (!is_string($v) || trim($v) === '') {
        return null;
    }
    return trim($v);
}

function api_db_config(): array
{
    $portStr = api_env_optional('MCI_DB_PORT');
    $port    = ($portStr !== null && $portStr !== '' && ctype_digit($portStr)) ? (int)$portStr : null;

    return [
        'host'       => api_env('MCI_DB_HOST'),
        'name'       => api_env('MCI_DB_NAME'),
        'user'       => api_env('MCI_DB_USER'),
        'pass'       => api_env('MCI_DB_PASS'),
        'port'       => $port,
        // Default to persistent connections to avoid shared-host max_connections_per_hour exhaustion.
        // Set MCI_DB_PERSISTENT=0 to explicitly disable.
        'persistent' => api_env_flag_default('MCI_DB_PERSISTENT', true),
    ];
}

function api_jwt_secret(): string
{
    return api_env('MCI_JWT_SECRET');
}

/** True if env var is 1 / true / yes (case-insensitive). */
function api_env_flag(string $key): bool
{
    $v = getenv($key);
    if (!is_string($v) || $v === '') {
        $v = $_ENV[$key] ?? $_SERVER[$key] ?? '';
    }
    if (!is_string($v)) {
        return false;
    }
    $v = strtolower(trim($v));

    return $v === '1' || $v === 'true' || $v === 'yes';
}

/** True/false env flag with explicit default when unset. */
function api_env_flag_default(string $key, bool $default): bool
{
    $v = getenv($key);
    if (!is_string($v) || trim($v) === '') {
        $v = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    }
    if (!is_string($v) || trim($v) === '') {
        return $default;
    }
    $v = strtolower(trim($v));

    if ($v === '1' || $v === 'true' || $v === 'yes' || $v === 'on') {
        return true;
    }
    if ($v === '0' || $v === 'false' || $v === 'no' || $v === 'off') {
        return false;
    }

    return $default;
}

