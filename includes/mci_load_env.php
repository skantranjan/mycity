<?php

declare(strict_types=1);

/**
 * Load key=value pairs from a .env file into getenv() / $_ENV / $_SERVER.
 * No external dependency — safe for simple dev/hosting setups.
 *
 * Rules:
 * - Lines starting with # are comments (after optional leading whitespace).
 * - Format: KEY=value or KEY="value" or KEY='value'
 * - Trailing \r is stripped; empty lines ignored.
 */
function mci_load_dotenv(?string $envFilePath = null): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }

    $root = dirname(__DIR__);
    $path = $envFilePath ?? ($root . DIRECTORY_SEPARATOR . '.env');
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || ($line[0] ?? '') === '#') {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        if ($key === '') {
            continue;
        }
        $value = trim($value);
        $len = strlen($value);
        if ($len >= 2) {
            $q0 = $value[0];
            $q1 = $value[$len - 1];
            if (($q0 === '"' && $q1 === '"') || ($q0 === "'" && $q1 === "'")) {
                $value = substr($value, 1, -1);
            }
        }
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    $loaded = true;
}
