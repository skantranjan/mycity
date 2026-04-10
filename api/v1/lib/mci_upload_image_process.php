<?php
declare(strict_types=1);

/**
 * Resize and re-encode business listing uploads to reduce storage and bandwidth.
 * Prefers JPEG output; copies small GIFs unchanged to preserve animation.
 *
 * @return non-empty-string|null Basename written (e.g. uuid.jpg), or null if caller should fall back to moving the original.
 */
function mci_business_upload_optimize(
    string $tmpPath,
    string $mime,
    string $destDir,
    string $uuidBase,
    int $maxBytes,
    int $maxEdge
): ?string {
    if (!extension_loaded('gd')) {
        return null;
    }

    $rawSize = @filesize($tmpPath);
    if ($rawSize === false) {
        return null;
    }

    // Keep animated GIFs when already within the size limit.
    if ($mime === 'image/gif' && $rawSize <= $maxBytes) {
        $dest = $destDir . '/' . $uuidBase . '.gif';
        return @copy($tmpPath, $dest) ? $uuidBase . '.gif' : null;
    }

    $data = @file_get_contents($tmpPath);
    if ($data === false || $data === '') {
        return null;
    }

    $im = @imagecreatefromstring($data);
    if ($im === false) {
        return null;
    }

    if (function_exists('imagepalettetotruecolor')) {
        @imagepalettetotruecolor($im);
    }

    // Flatten alpha onto white for consistent JPEG output.
    imagealphablending($im, true);
    $w = imagesx($im);
    $h = imagesy($im);
    if ($w < 1 || $h < 1) {
        imagedestroy($im);
        return null;
    }
    $flat = imagecreatetruecolor($w, $h);
    if ($flat === false) {
        imagedestroy($im);
        return null;
    }
    $white = imagecolorallocate($flat, 255, 255, 255);
    imagefill($flat, 0, 0, $white);
    imagealphablending($flat, true);
    imagecopy($flat, $im, 0, 0, 0, 0, $w, $h);
    imagedestroy($im);
    $im = $flat;

    $jpgPath = $destDir . '/' . $uuidBase . '.jpg';
    $quality = 86;
    $minQuality = 52;
    $edgeLimit = $maxEdge;
    $minEdge = 640;
    $guard = 0;

    while ($guard++ < 48) {
        $curW = imagesx($im);
        $curH = imagesy($im);
        $long = max($curW, $curH);
        if ($long > $edgeLimit) {
            $ratio = $edgeLimit / $long;
            $nw = max(1, (int) round($curW * $ratio));
            $nh = max(1, (int) round($curH * $ratio));
            $scaled = imagescale($im, $nw, $nh, IMG_BILINEAR_FIXED);
            if ($scaled !== false) {
                imagedestroy($im);
                $im = $scaled;
            }
        }

        if (!@imagejpeg($im, $jpgPath, $quality)) {
            imagedestroy($im);
            @unlink($jpgPath);
            return null;
        }
        $outSize = @filesize($jpgPath);
        if ($outSize !== false && $outSize <= $maxBytes) {
            imagedestroy($im);
            return $uuidBase . '.jpg';
        }

        if ($quality > $minQuality) {
            $quality -= 6;
            continue;
        }

        if ($edgeLimit > $minEdge) {
            $edgeLimit = (int) max($minEdge, (int) round($edgeLimit * 0.88));
            $quality = 86;
            continue;
        }

        break;
    }

    imagedestroy($im);
    @unlink($jpgPath);

    return null;
}
