<?php

declare(strict_types=1);

/**
 * Simple file-backed rate limiter (flock). Fails open if storage is unusable.
 *
 * @return bool true if request is allowed, false if over limit
 */
function api_rate_limit_allow(string $bucketKey, int $maxAttempts, int $windowSeconds): bool
{
    if ($maxAttempts < 1 || $windowSeconds < 1) {
        return true;
    }

    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mci_rate_limit';
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
        return true;
    }

    $file = $dir . DIRECTORY_SEPARATOR . hash('sha256', $bucketKey) . '.json';
    $now  = time();

    $fp = fopen($file, 'c+');
    if ($fp === false) {
        return true;
    }

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);

        return true;
    }

    $raw  = stream_get_contents($fp);
    $data = ($raw !== false && $raw !== '') ? json_decode($raw, true) : null;
    if (!is_array($data) || !isset($data['reset_at'], $data['count'])) {
        $data = ['count' => 0, 'reset_at' => $now + $windowSeconds];
    }
    if ($now > (int) $data['reset_at']) {
        $data = ['count' => 0, 'reset_at' => $now + $windowSeconds];
    }

    $data['count'] = (int) $data['count'] + 1;
    $allowed       = $data['count'] <= $maxAttempts;

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_UNESCAPED_SLASHES));
    fflush($fp);
    flock($fp, UNLOCK);
    fclose($fp);

    return $allowed;
}
