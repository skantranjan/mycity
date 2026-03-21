<?php

declare(strict_types=1);

/** Read optional boolean env without throwing (for PHP pages). */
function mci_env_flag(string $key): bool
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
