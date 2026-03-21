<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/slug.php';

/**
 * Generate a globally unique slug (mci_categories.slug is UNIQUE).
 */
function api_category_next_unique_slug(PDO $pdo, string $baseName, ?int $excludeId = null): string
{
    $slug = api_slugify($baseName);
    if ($slug === '') {
        $slug = 'category';
    }
    $candidate = $slug;
    $n = 0;
    while (true) {
        if ($excludeId !== null) {
            $stmt = $pdo->prepare('SELECT id FROM mci_categories WHERE slug = ? AND id <> ? LIMIT 1');
            $stmt->execute([$candidate, $excludeId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM mci_categories WHERE slug = ? LIMIT 1');
            $stmt->execute([$candidate]);
        }
        if (!$stmt->fetch()) {
            return $candidate;
        }
        $n++;
        $candidate = $slug . '-' . $n;
    }
}

/**
 * Parent must exist and be a root category (only one level of subcategories).
 */
function api_category_validate_parent_for_child(PDO $pdo, ?int $parentId): void
{
    if ($parentId === null || $parentId <= 0) {
        return;
    }
    $stmt = $pdo->prepare('SELECT id, parent_id FROM mci_categories WHERE id = ? LIMIT 1');
    $stmt->execute([$parentId]);
    $row = $stmt->fetch();
    if (!$row) {
        api_error('parent_category_not_found', 404);
    }
    if ($row['parent_id'] !== null) {
        api_error('parent_must_be_root_category', 400);
    }
}

function api_category_count_children(PDO $pdo, int $id): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM mci_categories WHERE parent_id = ?');
    $stmt->execute([$id]);

    return (int) ($stmt->fetch()['c'] ?? 0);
}
