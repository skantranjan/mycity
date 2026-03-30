<?php

declare(strict_types=1);

/**
 * Search products or services with pagination and filters.
 *
 * $params keys:
 *   type      string  'products' | 'services'   (required)
 *   q         string  keyword (name + desc)      (optional)
 *   city      string  branch city, case-insensitive (optional)
 *   category  string  parent category slug       (optional)
 *   price_min float   starting-price lower bound (optional)
 *   price_max float   starting-price upper bound (optional)
 *   sort      string  relevance|newest|price_asc|price_desc (default: relevance)
 *   page      int     1-based page number        (default: 1)
 *   per_page  int     results per page, max 48   (default: 12)
 *
 * Returns:
 *   ['ok'=>true, 'total'=>int, 'page'=>int, 'per_page'=>int, 'items'=>array]
 *   ['ok'=>false, 'error'=>string, 'status'=>int]
 */
function api_items_search(PDO $pdo, array $params): array
{
    // ── Validate & sanitise inputs ────────────────────────────────────────────
    $type = trim((string)($params['type'] ?? ''));
    if (!in_array($type, ['products', 'services'], true)) {
        return ['ok' => false, 'error' => 'invalid_type', 'status' => 400];
    }
    $table = $type === 'products' ? 'mci_business_products' : 'mci_business_services';

    $q        = substr(strip_tags(trim((string)($params['q']       ?? ''))), 0, 120);
    $city     = substr(strip_tags(trim((string)($params['city']    ?? ''))), 0, 80);
    $category = trim((string)($params['category'] ?? ''));
    if ($category !== '' && !preg_match('/^[a-z0-9-]+$/i', $category)) {
        $category = '';
    }
    $category = substr($category, 0, 80);

    $priceMin = (isset($params['price_min']) && $params['price_min'] !== '') ? max(0.0, (float)$params['price_min']) : null;
    $priceMax = (isset($params['price_max']) && $params['price_max'] !== '') ? max(0.0, (float)$params['price_max']) : null;
    if ($priceMin !== null && $priceMax !== null && $priceMin > $priceMax) {
        [$priceMin, $priceMax] = [$priceMax, $priceMin];
    }

    $sortWhitelist = ['relevance', 'newest', 'price_asc', 'price_desc'];
    $sort = in_array($params['sort'] ?? '', $sortWhitelist, true) ? $params['sort'] : 'relevance';

    $page    = max(1, min(999, (int)($params['page']     ?? 1)));
    $perPage = max(1, min(48,  (int)($params['per_page'] ?? 12)));
    $offset  = ($page - 1) * $perPage;

    // ── Build WHERE clause ────────────────────────────────────────────────────
    $where  = ['p.is_active = 1', "g.status = 'live'"];
    $binds  = [];  // positional bind values for the main query

    if ($q !== '') {
        $like = '%' . $q . '%';
        $where[]  = '(p.name LIKE ? OR p.description LIKE ?)';
        $binds[]  = $like;
        $binds[]  = $like;
    }

    if ($city !== '') {
        $where[]  = 'EXISTS (SELECT 1 FROM mci_business_branches WHERE business_group_id = g.id AND status = \'active\' AND LOWER(city) = LOWER(?))';
        $binds[]  = $city;
    }

    if ($category !== '') {
        $where[]  = 'c.slug = ?';
        $binds[]  = $category;
    }

    if ($priceMax !== null) {
        $where[]  = '(p.price_min <= ? OR p.price_min IS NULL)';
        $binds[]  = $priceMax;
    }

    if ($priceMin !== null) {
        $where[]  = '(p.price_min >= ? OR p.price_min IS NULL)';
        $binds[]  = $priceMin;
    }

    $whereSql = implode(' AND ', $where);

    // ── Count total (for pagination) ──────────────────────────────────────────
    $countSql = "
        SELECT COUNT(*) AS total
        FROM {$table} p
        INNER JOIN mci_business_groups g ON g.id = p.business_group_id
        LEFT  JOIN mci_categories c      ON c.id = g.parent_category_id
        WHERE {$whereSql}
    ";
    try {
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($binds);
        $total = (int)($countStmt->fetchColumn() ?: 0);
    } catch (Throwable $e) {
        error_log('api_items_search count error: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'server_error', 'status' => 500];
    }

    // ── Build ORDER BY ────────────────────────────────────────────────────────
    $orderBinds = [];
    switch ($sort) {
        case 'price_asc':
            $orderSql = 'ORDER BY ISNULL(p.price_min), p.price_min ASC';
            break;
        case 'price_desc':
            $orderSql = 'ORDER BY ISNULL(p.price_min), p.price_min DESC';
            break;
        case 'newest':
            $orderSql = 'ORDER BY p.created_at DESC';
            break;
        default: // relevance
            if ($q !== '') {
                $orderSql    = 'ORDER BY CASE WHEN p.name LIKE ? THEN 0 ELSE 1 END, p.created_at DESC';
                $orderBinds[] = '%' . $q . '%';
            } else {
                $orderSql = 'ORDER BY p.created_at DESC';
            }
            break;
    }

    // ── Main SELECT ───────────────────────────────────────────────────────────
    $selectSql = "
        SELECT
            p.id, p.name, p.description,
            p.price_min, p.price_max, p.price_unit,
            p.image_path,
            g.id   AS business_group_id,
            g.name AS business_name,
            g.slug AS business_slug,
            g.logo_path AS business_logo,
            c.name AS business_category,
            COALESCE(
                (SELECT city FROM mci_business_branches
                 WHERE business_group_id = g.id AND status = 'active' AND is_primary = 1 LIMIT 1),
                (SELECT city FROM mci_business_branches
                 WHERE business_group_id = g.id AND status = 'active' LIMIT 1)
            ) AS city
        FROM {$table} p
        INNER JOIN mci_business_groups g ON g.id = p.business_group_id
        LEFT  JOIN mci_categories c      ON c.id = g.parent_category_id
        WHERE {$whereSql}
        {$orderSql}
        LIMIT ? OFFSET ?
    ";

    $mainBinds = array_merge($binds, $orderBinds, [$perPage, $offset]);

    try {
        $stmt = $pdo->prepare($selectSql);
        $stmt->execute($mainBinds);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('api_items_search select error: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'server_error', 'status' => 500];
    }

    // ── Normalise output ──────────────────────────────────────────────────────
    $items = [];
    foreach ($rows as $row) {
        $items[] = [
            'id'                => (string)$row['id'],
            'name'              => (string)$row['name'],
            'description'       => (string)($row['description'] ?? ''),
            'price_min'         => $row['price_min'] !== null ? (float)$row['price_min'] : null,
            'price_max'         => $row['price_max'] !== null ? (float)$row['price_max'] : null,
            'price_unit'        => (string)($row['price_unit'] ?? ''),
            'image_path'        => (string)($row['image_path'] ?? ''),
            'business_group_id' => (string)$row['business_group_id'],
            'business_name'     => (string)$row['business_name'],
            'business_slug'     => (string)$row['business_slug'],
            'business_logo'     => (string)($row['business_logo'] ?? ''),
            'business_category' => (string)($row['business_category'] ?? ''),
            'city'              => (string)($row['city'] ?? ''),
        ];
    }

    return [
        'ok'       => true,
        'total'    => $total,
        'page'     => $page,
        'per_page' => $perPage,
        'pages'    => (int)ceil($total / $perPage),
        'items'    => $items,
    ];
}
