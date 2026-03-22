<?php

declare(strict_types=1);

/**
 * Favourites helpers — save/remove/list per-user favourite businesses.
 * Requires api_db() from api/v1/lib/db.php to be loaded first.
 */

function mci_favourites_is_saved(PDO $pdo, string $userId, string $businessGroupId): bool
{
    if ($userId === '' || $businessGroupId === '') {
        return false;
    }
    try {
        $st = $pdo->prepare(
            "SELECT 1 FROM mci_user_favourites WHERE user_id = ? AND business_group_id = ? LIMIT 1"
        );
        $st->execute([$userId, $businessGroupId]);
        return (bool) $st->fetchColumn();
    } catch (Throwable $ignored) {
        return false;
    }
}

/**
 * @return array{ok: bool, saved: bool, error?: string}
 *   saved=true  → just added, saved=false → just removed
 */
function mci_favourites_toggle(PDO $pdo, string $userId, string $businessGroupId): array
{
    if ($userId === '' || $businessGroupId === '') {
        return ['ok' => false, 'saved' => false, 'error' => 'Invalid request.'];
    }
    try {
        if (mci_favourites_is_saved($pdo, $userId, $businessGroupId)) {
            $pdo->prepare(
                "DELETE FROM mci_user_favourites WHERE user_id = ? AND business_group_id = ?"
            )->execute([$userId, $businessGroupId]);
            return ['ok' => true, 'saved' => false];
        }
        $id = sprintf(
            '%08x-%04x-4%03x-%04x-%012x',
            random_int(0, 0xFFFFFFFF),
            random_int(0, 0xFFFF),
            random_int(0, 0x0FFF),
            random_int(0x8000, 0xBFFF),
            random_int(0, 0xFFFFFFFFFFFF)
        );
        $pdo->prepare(
            "INSERT IGNORE INTO mci_user_favourites (id, user_id, business_group_id) VALUES (?, ?, ?)"
        )->execute([$id, $userId, $businessGroupId]);
        return ['ok' => true, 'saved' => true];
    } catch (Throwable $e) {
        return ['ok' => false, 'saved' => false, 'error' => 'Could not update favourites.'];
    }
}

/**
 * Returns rows: id, name, slug, category_name, city, created_at (of favourite)
 */
function mci_favourites_list(PDO $pdo, string $userId): array
{
    if ($userId === '') {
        return [];
    }
    try {
        $st = $pdo->prepare("
            SELECT
                g.id AS business_group_id,
                g.name,
                g.slug,
                c.name  AS category_name,
                b.city,
                b.address_line1,
                b.address_line2,
                b.state,
                b.pincode,
                f.created_at AS saved_at
            FROM mci_user_favourites f
            INNER JOIN mci_business_groups g ON g.id = f.business_group_id AND g.status != 'deleted'
            LEFT  JOIN mci_categories c ON c.id = g.parent_category_id
            LEFT  JOIN mci_business_branches b
                    ON b.business_group_id = g.id
                   AND b.id = (
                         SELECT b2.id FROM mci_business_branches b2
                         WHERE b2.business_group_id = g.id
                         ORDER BY b2.is_primary DESC, b2.created_at ASC
                         LIMIT 1
                       )
            WHERE f.user_id = ?
            ORDER BY f.created_at DESC
        ");
        $st->execute([$userId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $ignored) {
        return [];
    }
}
