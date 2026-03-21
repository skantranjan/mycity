<?php

declare(strict_types=1);

require_once __DIR__ . '/slug.php';

/**
 * Globally unique slug for mci_tags.slug (UNIQUE).
 */
function api_tag_next_unique_slug(PDO $pdo, string $baseName, ?int $excludeId = null): string
{
    $slug = api_slugify($baseName);
    if ($slug === '') {
        $slug = 'tag';
    }
    $candidate = $slug;
    $n = 0;
    while (true) {
        if ($excludeId !== null) {
            $stmt = $pdo->prepare('SELECT id FROM mci_tags WHERE slug = ? AND id <> ? LIMIT 1');
            $stmt->execute([$candidate, $excludeId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM mci_tags WHERE slug = ? LIMIT 1');
            $stmt->execute([$candidate]);
        }
        if (!$stmt->fetch()) {
            return $candidate;
        }
        $n++;
        $candidate = $slug . '-' . $n;
    }
}
