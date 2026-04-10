<?php

declare(strict_types=1);

require_once __DIR__ . '/uuid.php';
require_once __DIR__ . '/business_helpers.php';
require_once __DIR__ . '/auth_direct.php';
require_once __DIR__ . '/location_service.php';

// ---------------------------------------------------------------------------
// Create
// ---------------------------------------------------------------------------

/**
 * Create a business group + first branch inside a transaction.
 *
 * If context='guest' and data['posting_type']='registered', a new subscriber
 * account is created first (email + password required in data['account']).
 *
 * Returns ['ok'=>true, 'id'=>$groupId, 'slug'=>$slug, 'branch_id'=>$branchId]
 *      or ['ok'=>false, 'error'=>string, 'status'=>int]
 */
function api_business_create(PDO $pdo, array $data, ?array $auth): array
{
    // -----------------------------------------------------------------------
    // 1) Guest "with account" — create subscriber first (outside transaction
    //    so we get the userId before we open our own)
    // -----------------------------------------------------------------------
    $context = (string)($data['context'] ?? 'guest');
    if ($context === 'guest' && ($data['posting_type'] ?? '') === 'registered') {
        $accountData = $data['account'] ?? [];
        $accountData['accept_terms']   = true;
        $accountData['accept_privacy'] = true;

        $reg = api_direct_subscriber_register($accountData);
        if (!$reg['ok']) {
            return [
                'ok'     => false,
                'error'  => $reg['error'] ?? 'account_creation_failed',
                'status' => $reg['status'] ?? 400,
            ];
        }
        // Promote context to subscriber with the new user
        $data['context'] = 'subscriber';
        $auth = ['user_id' => $reg['user']['id'], 'role' => 'subscriber'];
        // Return the JWT so the client can log in
        $newUserJwt = $reg['token'] ?? null;
        $newUserExp = $reg['exp'] ?? null;
    }

    // -----------------------------------------------------------------------
    // 2) Resolve context → DB values
    // -----------------------------------------------------------------------
    $ctx = api_business_resolve_context($data, $auth);

    // -----------------------------------------------------------------------
    // 3) Validate minimum requirements
    // -----------------------------------------------------------------------
    $minError = api_business_validate_minimum($data);
    if ($minError !== null) {
        return ['ok' => false, 'error' => $minError, 'status' => 400];
    }

    // -----------------------------------------------------------------------
    // 4) Unpack & sanitise
    // -----------------------------------------------------------------------
    $group  = $data['group']  ?? [];
    $branch = $data['branch'] ?? [];

    $name        = trim((string)($group['name'] ?? ''));
    $tagline     = trim((string)($group['tagline'] ?? ''));
    $description = trim((string)($group['description'] ?? ''));
    $videoUrl    = trim((string)($group['video_url'] ?? ''));
    $pageTitle   = trim((string)($group['page_title'] ?? ''));
    $metaDesc    = trim((string)($group['meta_description'] ?? ''));
    $metaKey     = trim((string)($group['meta_keywords'] ?? ''));
    $priceRange  = (string)($group['price_range'] ?? '');
    $categoryId  = (int)($group['category_id'] ?? 0);
    if ($categoryId <= 0) {
        return ['ok' => false, 'error' => 'category_required', 'status' => 400];
    }
    $subcategoryIds = array_map('intval', (array)($group['subcategory_ids'] ?? []));
    $tagIds         = array_map('intval', (array)($group['tag_ids'] ?? []));

    $logoPah    = trim((string)($group['logo_path'] ?? ''));
    $profilePah = trim((string)($group['profile_path'] ?? ''));
    $bannerPah  = trim((string)($group['banner_path'] ?? ''));

    $actorId    = $ctx['added_by_user_id'];
    $productIds = [];   // ← add
    $serviceIds = [];   // ← add

    // -----------------------------------------------------------------------
    // 5) Generate slugs
    // -----------------------------------------------------------------------
    $groupSlug  = api_business_group_next_unique_slug($pdo, $name);
    $city       = trim((string)($branch['city'] ?? ''));
    $branchSlug = api_business_branch_next_unique_slug($pdo, $groupSlug, $city !== '' ? $city : 'branch');

    // -----------------------------------------------------------------------
    // 6) IDs
    // -----------------------------------------------------------------------
    $groupId  = api_uuid_v4();
    $branchId = api_uuid_v4();

    // -----------------------------------------------------------------------
    // 7) Transaction
    // -----------------------------------------------------------------------
    $branchState   = '';
    $branchCountry = 'India';
    $pdo->beginTransaction();
    try {
        // --- mci_business_groups ---
        $pdo->prepare('
            INSERT INTO mci_business_groups
              (id, name, slug, tagline, description, parent_category_id,
               price_range, video_url,
               page_title, meta_description, meta_keywords,
               logo_path, profile_path, banner_path,
               status, added_by_role, added_by_user_id,
               created_by_user_id)
            VALUES
              (?, ?, ?, ?, ?, ?,
               ?, ?,
               ?, ?, ?,
               ?, ?, ?,
               ?, ?, ?,
               ?)
        ')->execute([
            $groupId,
            $name,
            $groupSlug,
            $tagline !== '' ? $tagline : null,
            $description !== '' ? $description : null,
            $categoryId,
            in_array($priceRange, ['free', 'moderate', 'pricey', 'ultra'], true) ? $priceRange : null,
            $videoUrl !== '' ? $videoUrl : null,
            $pageTitle !== '' ? $pageTitle : null,
            $metaDesc !== '' ? $metaDesc : null,
            $metaKey !== '' ? $metaKey : null,
            $logoPah !== '' ? $logoPah : null,
            $profilePah !== '' ? $profilePah : null,
            $bannerPah !== '' ? $bannerPah : null,
            $ctx['status'],
            $ctx['added_by_role'],
            $ctx['added_by_user_id'],
            $actorId,
        ]);

        // --- mci_business_subcategories ---
        if ($subcategoryIds !== []) {
            $subStmt = $pdo->prepare('
                INSERT IGNORE INTO mci_business_subcategories
                  (id, business_group_id, category_id, created_by_user_id)
                VALUES (?, ?, ?, ?)
            ');
            foreach ($subcategoryIds as $scId) {
                if ($scId > 0) {
                    $subStmt->execute([api_uuid_v4(), $groupId, $scId, $actorId]);
                }
            }
        }

        // --- mci_business_tags ---
        if ($tagIds !== []) {
            $tagStmt = $pdo->prepare('
                INSERT IGNORE INTO mci_business_tags
                  (id, business_group_id, tag_id, created_by_user_id)
                VALUES (?, ?, ?, ?)
            ');
            foreach ($tagIds as $tId) {
                if ($tId > 0) {
                    $tagStmt->execute([api_uuid_v4(), $groupId, $tId, $actorId]);
                }
            }
        }

        // --- mci_business_branches ---
        $branchState   = mb_substr(trim((string)($branch['state']   ?? '')), 0, 100) ?: '';
        $branchCountry = mb_substr(trim((string)($branch['country'] ?? 'India')), 0, 100);
        if ($branchCountry === '') { $branchCountry = 'India'; }

        $pdo->prepare('
            INSERT INTO mci_business_branches
              (id, business_group_id, slug, address_line1, address_line2, city, state, country,
               pincode, latitude, longitude,
               phone_primary, phone_secondary, whatsapp_number, website,
               status, created_by_user_id)
            VALUES
              (?, ?, ?, ?, ?, ?, ?, ?,
               ?, ?, ?,
               ?, ?, ?, ?,
               \'active\', ?)
        ')->execute([
            $branchId,
            $groupId,
            $branchSlug,
            trim((string)($branch['full_address']    ?? '')) ?: '',     // address_line1 is NOT NULL; empty string is safe
            trim((string)($branch['address_line2']   ?? '')) ?: null,
            $city !== '' ? $city : '',
            $branchState  !== '' ? $branchState  : null,
            $branchCountry,
            trim((string)($branch['pincode']         ?? '')) ?: null,
            trim((string)($branch['latitude']        ?? '')) ?: null,
            trim((string)($branch['longitude']       ?? '')) ?: null,
            trim((string)($branch['phone']           ?? '')) ?: null,  // JS sends as 'phone'; maps to phone_primary
            trim((string)($branch['phone_secondary'] ?? '')) ?: null,
            trim((string)($branch['whatsapp']        ?? '')) ?: null,  // JS sends as 'whatsapp'; maps to whatsapp_number
            trim((string)($branch['website']         ?? '')) ?: null,
            $actorId,
        ]);

        // --- mci_business_branch_hours ---
        $hours = $branch['hours'] ?? [];
        if (is_array($hours) && $hours !== []) {
            $hourStmt = $pdo->prepare('
                INSERT INTO mci_business_branch_hours
                  (id, branch_id, day_of_week, opens_at, closes_at,
                   opens_at_2, closes_at_2, is_closed, created_by_user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                  opens_at=VALUES(opens_at), closes_at=VALUES(closes_at),
                  opens_at_2=VALUES(opens_at_2), closes_at_2=VALUES(closes_at_2),
                  is_closed=VALUES(is_closed)
            ');
            $validDays = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
            foreach ($validDays as $day) {
                if (!isset($hours[$day])) {
                    continue;
                }
                $h = $hours[$day];
                $isOpen     = !empty($h['open']);
                $slot1Start = trim((string)($h['slot1_start'] ?? '')) ?: null;
                $slot1End   = trim((string)($h['slot1_end']   ?? '')) ?: null;
                $slot2Start = trim((string)($h['slot2_start'] ?? '')) ?: null;
                $slot2End   = trim((string)($h['slot2_end']   ?? '')) ?: null;
                $hourStmt->execute([
                    api_uuid_v4(), $branchId, $day,
                    $isOpen ? $slot1Start : null,
                    $isOpen ? $slot1End   : null,
                    $isOpen ? $slot2Start : null,
                    $isOpen ? $slot2End   : null,
                    $isOpen ? 0 : 1,
                    $actorId,
                ]);
            }
        }

        // --- mci_business_products ---
        $products = $data['products'] ?? [];
        if (is_array($products)) {
            $prodStmt = $pdo->prepare('
                INSERT INTO mci_business_products
                  (id, business_group_id, name, description,
                   price_min, price_max, price_unit, image_path,
                   sort_order, created_by_user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            foreach ($products as $i => $p) {
                $pName = trim((string)($p['name'] ?? ''));
                if ($pName === '') {
                    continue;
                }
                $pid = api_uuid_v4();       // generate once
                $productIds[] = $pid;       // collect only rows that are actually inserted
                $prodStmt->execute([
                    $pid, $groupId,
                    $pName,
                    trim((string)($p['description'] ?? '')) ?: null,
                    is_numeric($p['price_min'] ?? '') ? (float)$p['price_min'] : null,
                    is_numeric($p['price_max'] ?? '') ? (float)$p['price_max'] : null,
                    trim((string)($p['price_unit'] ?? 'INR')) ?: 'INR',
                    trim((string)($p['image_path'] ?? '')) ?: null,
                    $i,
                    $actorId,
                ]);
            }
        }

        // --- mci_business_services ---
        $services = $data['services'] ?? [];
        if (is_array($services)) {
            $svcStmt = $pdo->prepare('
                INSERT INTO mci_business_services
                  (id, business_group_id, name, description,
                   price_min, price_max, price_unit, image_path,
                   sort_order, created_by_user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            foreach ($services as $i => $s) {
                $sName = trim((string)($s['name'] ?? ''));
                if ($sName === '') {
                    continue;
                }
                $sid = api_uuid_v4();       // generate once
                $serviceIds[] = $sid;       // collect only rows that are actually inserted
                $svcStmt->execute([
                    $sid, $groupId,
                    $sName,
                    trim((string)($s['description'] ?? '')) ?: null,
                    is_numeric($s['price_min'] ?? '') ? (float)$s['price_min'] : null,
                    is_numeric($s['price_max'] ?? '') ? (float)$s['price_max'] : null,
                    trim((string)($s['price_unit'] ?? 'INR')) ?: 'INR',
                    trim((string)($s['image_path'] ?? '')) ?: null,
                    $i,
                    $actorId,
                ]);
            }
        }

        // --- mci_business_faqs ---
        $faqs = $data['faqs'] ?? [];
        if (is_array($faqs)) {
            $faqStmt = $pdo->prepare('
                INSERT INTO mci_business_faqs
                  (id, business_group_id, question, answer, sort_order, created_by_user_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            foreach ($faqs as $i => $f) {
                $q = trim((string)($f['question'] ?? ''));
                if ($q === '') {
                    continue;
                }
                $faqStmt->execute([
                    api_uuid_v4(), $groupId,
                    $q,
                    trim((string)($f['answer'] ?? '')),
                    $i,
                    $actorId,
                ]);
            }
        }

        // --- mci_business_images (gallery) ---
        $galleryPaths = $data['gallery_paths'] ?? [];
        if (is_array($galleryPaths)) {
            $imgStmt = $pdo->prepare('
                INSERT INTO mci_business_images
                  (id, business_group_id, file_path, sort_order,
                   uploaded_by_user_id, created_by_user_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            foreach ($galleryPaths as $i => $path) {
                $path = trim((string)$path);
                if ($path === '') {
                    continue;
                }
                $imgStmt->execute([
                    api_uuid_v4(), $groupId,
                    $path, $i,
                    $actorId, $actorId,
                ]);
            }
        }

        // --- mci_business_social_links ---
        $socialLinks = $branch['social_links'] ?? [];
        $platformMap = [
            'facebook'  => 'facebook',
            'instagram' => 'instagram',
            'x'         => 'twitter',
            'youtube'   => 'youtube',
            'linkedin'  => 'linkedin',
            'tiktok'    => 'tiktok',
        ];
        if (is_array($socialLinks)) {
            $socStmt = $pdo->prepare('
                INSERT INTO mci_business_social_links
                  (id, business_group_id, platform, url, created_by_user_id)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE url=VALUES(url)
            ');
            foreach ($platformMap as $inputKey => $dbPlatform) {
                $url = trim((string)($socialLinks[$inputKey] ?? ''));
                if ($url !== '') {
                    $socStmt->execute([api_uuid_v4(), $groupId, $dbPlatform, $url, $actorId]);
                }
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        if (function_exists('mci_log_error')) {
            mci_log_error('api_business_create', $e);
        } else {
            error_log('api_business_create: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
        return [
            'ok'     => false,
            'error'  => 'server_error',
            'detail' => get_class($e) . ': ' . $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine(),
            'status' => 500,
        ];
    }

    // Sync to mci_locations — outside transaction; failure must never block listing
    try {
        api_locations_upsert($pdo, $branchCountry, $branchState, $city);
    } catch (Throwable $ignored) {}

    $result = [
        'ok'          => true,
        'id'          => $groupId,
        'slug'        => $groupSlug,
        'branch_id'   => $branchId,
        'product_ids' => $productIds,
        'service_ids' => $serviceIds,
    ];
    if (isset($newUserJwt)) {
        $result['token'] = $newUserJwt;
        $result['token_exp'] = $newUserExp;
    }
    return $result;
}

// ---------------------------------------------------------------------------
// Fetch single group (full denormalised)
// ---------------------------------------------------------------------------

function api_business_fetch(PDO $pdo, string $groupId): ?array
{
    $stmt = $pdo->prepare('
        SELECT g.*, c.name AS category_name, c.slug AS category_slug
        FROM mci_business_groups g
        LEFT JOIN mci_categories c ON g.parent_category_id = c.id
        WHERE g.id = ? AND g.status != \'deleted\'
        LIMIT 1
    ');
    $stmt->execute([$groupId]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$group) {
        return null;
    }

    // branches
    $stmt = $pdo->prepare('SELECT * FROM mci_business_branches WHERE business_group_id = ? AND status != \'deleted\' ORDER BY created_at');
    $stmt->execute([$groupId]);
    $group['branches'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // tags
    $stmt = $pdo->prepare('SELECT t.id, t.name, t.slug FROM mci_tags t JOIN mci_business_tags bt ON bt.tag_id = t.id WHERE bt.business_group_id = ? ORDER BY t.name');
    $stmt->execute([$groupId]);
    $group['tags'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // subcategories
    $stmt = $pdo->prepare('SELECT c.id, c.name, c.slug FROM mci_categories c JOIN mci_business_subcategories bs ON bs.category_id = c.id WHERE bs.business_group_id = ? ORDER BY c.name');
    $stmt->execute([$groupId]);
    $group['subcategories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // products
    $stmt = $pdo->prepare('SELECT * FROM mci_business_products WHERE business_group_id = ? ORDER BY sort_order, created_at');
    $stmt->execute([$groupId]);
    $group['products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // services
    $stmt = $pdo->prepare('SELECT * FROM mci_business_services WHERE business_group_id = ? ORDER BY sort_order, created_at');
    $stmt->execute([$groupId]);
    $group['services'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // faqs
    $stmt = $pdo->prepare('SELECT * FROM mci_business_faqs WHERE business_group_id = ? ORDER BY sort_order, created_at');
    $stmt->execute([$groupId]);
    $group['faqs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // gallery images
    $stmt = $pdo->prepare('SELECT * FROM mci_business_images WHERE business_group_id = ? ORDER BY sort_order, created_at');
    $stmt->execute([$groupId]);
    $group['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // social links
    $stmt = $pdo->prepare('SELECT platform, url, label FROM mci_business_social_links WHERE business_group_id = ? ORDER BY platform');
    $stmt->execute([$groupId]);
    $group['social_links'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $group;
}

// ---------------------------------------------------------------------------
// Update (in-place)
// ---------------------------------------------------------------------------

/**
 * Update an existing business group + primary branch inside a transaction.
 *
 * Slug, status, and added_by_* columns are never touched.
 * All subcategories, tags, products, services, faqs, and social links are
 * fully replaced (DELETE + re-INSERT) so the caller does not need to diff.
 *
 * Returns ['ok'=>true, 'id'=>$groupId, 'product_ids'=>[...], 'service_ids'=>[...]]
 *      or ['ok'=>false, 'error'=>'server_error', 'detail'=>..., 'status'=>500]
 */
function api_business_update(PDO $pdo, string $groupId, array $data, string $actorId): array
{
    $productIds = [];
    $serviceIds = [];

    $group  = $data['group']  ?? [];
    $branch = $data['branch'] ?? [];

    $name        = trim((string)($group['name']        ?? ''));
    $tagline     = trim((string)($group['tagline']     ?? ''));
    $description = trim((string)($group['description'] ?? ''));
    $videoUrl    = trim((string)($group['video_url']   ?? ''));
    $pageTitle   = trim((string)($group['page_title'] ?? ''));
    $metaDesc    = trim((string)($group['meta_description'] ?? ''));
    $metaKey     = trim((string)($group['meta_keywords'] ?? ''));
    $priceRange  = (string)($group['price_range'] ?? '');
    $categoryId  = (int)($group['category_id'] ?? 0);
    if ($categoryId <= 0) {
        return ['ok' => false, 'error' => 'category_required', 'status' => 400];
    }
    $subcategoryIds = array_map('intval', (array)($group['subcategory_ids'] ?? []));
    $tagIds         = array_map('intval', (array)($group['tag_ids']         ?? []));
    $editSections   = array_map('strval', (array)($data['edit_sections'] ?? []));
    if ($editSections === []) {
        $editSections = ['core', 'products', 'services', 'seo', 'location', 'faqs', 'social'];
    }

    $logoPath   = trim((string)($group['logo_path']   ?? ''));
    $bannerPath = trim((string)($group['banner_path'] ?? ''));

    $pdo->beginTransaction();
    try {
        // --- mci_business_groups (update only safe fields) ---
        if (in_array('core', $editSections, true) || in_array('seo', $editSections, true)) {
            $pdo->prepare('
                UPDATE mci_business_groups
                SET name                = ?,
                    tagline             = ?,
                    description         = ?,
                    parent_category_id  = ?,
                    price_range         = ?,
                    video_url           = ?,
                    page_title          = ?,
                    meta_description    = ?,
                    meta_keywords       = ?,
                    logo_path           = ?,
                    banner_path         = ?
                WHERE id = ?
            ')->execute([
                $name !== '' ? $name : null,
                $tagline !== '' ? $tagline : null,
                $description !== '' ? $description : null,
                $categoryId,
                in_array($priceRange, ['free', 'moderate', 'pricey', 'ultra'], true) ? $priceRange : null,
                $videoUrl !== '' ? $videoUrl : null,
                $pageTitle !== '' ? $pageTitle : null,
                $metaDesc !== '' ? $metaDesc : null,
                $metaKey !== '' ? $metaKey : null,
                $logoPath !== '' ? $logoPath : null,
                $bannerPath !== '' ? $bannerPath : null,
                $groupId,
            ]);
        }

        // --- mci_business_subcategories: full replace ---
        if (in_array('core', $editSections, true)) {
            $pdo->prepare('DELETE FROM mci_business_subcategories WHERE business_group_id = ?')->execute([$groupId]);
        if ($subcategoryIds !== []) {
            $subStmt = $pdo->prepare('
                INSERT IGNORE INTO mci_business_subcategories
                  (id, business_group_id, category_id, created_by_user_id)
                VALUES (?, ?, ?, ?)
            ');
            foreach ($subcategoryIds as $scId) {
                if ($scId > 0) {
                    $subStmt->execute([api_uuid_v4(), $groupId, $scId, $actorId]);
                }
            }
        }
        }

        // --- mci_business_tags: full replace ---
        if (in_array('core', $editSections, true)) {
            $pdo->prepare('DELETE FROM mci_business_tags WHERE business_group_id = ?')->execute([$groupId]);
        if ($tagIds !== []) {
            $tagStmt = $pdo->prepare('
                INSERT IGNORE INTO mci_business_tags
                  (id, business_group_id, tag_id, created_by_user_id)
                VALUES (?, ?, ?, ?)
            ');
            foreach ($tagIds as $tId) {
                if ($tId > 0) {
                    $tagStmt->execute([api_uuid_v4(), $groupId, $tId, $actorId]);
                }
            }
        }
        }

        // --- primary branch: UPDATE (pick is_primary DESC, then oldest) ---
        $branchState   = mb_substr(trim((string)($branch['state']   ?? '')), 0, 100);
        $branchCountry = mb_substr(trim((string)($branch['country'] ?? 'India')), 0, 100);
        if ($branchCountry === '') { $branchCountry = 'India'; }
        $city = trim((string)($branch['city'] ?? ''));

        // Resolve the primary branch id so we can upsert hours against it
        $branchRow = $pdo->prepare('
            SELECT id FROM mci_business_branches
            WHERE business_group_id = ?
            ORDER BY is_primary DESC, created_at ASC
            LIMIT 1
        ');
        $branchRow->execute([$groupId]);
        $branchId = (string)($branchRow->fetchColumn() ?? '');

        if (in_array('location', $editSections, true) || in_array('core', $editSections, true)) {
            $pdo->prepare('
            UPDATE mci_business_branches
            SET address_line1   = ?,
                address_line2   = ?,
                city            = ?,
                state           = ?,
                country         = ?,
                pincode         = ?,
                latitude        = ?,
                longitude       = ?,
                phone_primary   = ?,
                phone_secondary = ?,
                whatsapp_number = ?,
                website         = ?
            WHERE business_group_id = ?
            ORDER BY is_primary DESC, created_at ASC
            LIMIT 1
        ')->execute([
            trim((string)($branch['full_address']    ?? '')) ?: '',
            trim((string)($branch['address_line2']   ?? '')) ?: null,
            $city !== '' ? $city : '',
            $branchState  !== '' ? $branchState  : null,
            $branchCountry,
            trim((string)($branch['pincode']         ?? '')) ?: null,
            trim((string)($branch['latitude']        ?? '')) ?: null,
            trim((string)($branch['longitude']       ?? '')) ?: null,
            trim((string)($branch['phone']           ?? '')) ?: null,
            trim((string)($branch['phone_secondary'] ?? '')) ?: null,
            trim((string)($branch['whatsapp']        ?? '')) ?: null,
            trim((string)($branch['website']         ?? '')) ?: null,
            $groupId,
        ]);
        }

        // --- mci_business_branch_hours: upsert (same ON DUPLICATE KEY pattern as create) ---
        if ($branchId !== '' && (in_array('location', $editSections, true) || in_array('core', $editSections, true))) {
            $hours = $branch['hours'] ?? [];
            if (is_array($hours) && $hours !== []) {
                $hourStmt = $pdo->prepare('
                    INSERT INTO mci_business_branch_hours
                      (id, branch_id, day_of_week, opens_at, closes_at,
                       opens_at_2, closes_at_2, is_closed, created_by_user_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                      opens_at=VALUES(opens_at), closes_at=VALUES(closes_at),
                      opens_at_2=VALUES(opens_at_2), closes_at_2=VALUES(closes_at_2),
                      is_closed=VALUES(is_closed)
                ');
                $validDays = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
                foreach ($validDays as $day) {
                    if (!isset($hours[$day])) {
                        continue;
                    }
                    $h = $hours[$day];
                    $isOpen     = !empty($h['open']);
                    $slot1Start = trim((string)($h['slot1_start'] ?? '')) ?: null;
                    $slot1End   = trim((string)($h['slot1_end']   ?? '')) ?: null;
                    $slot2Start = trim((string)($h['slot2_start'] ?? '')) ?: null;
                    $slot2End   = trim((string)($h['slot2_end']   ?? '')) ?: null;
                    $hourStmt->execute([
                        api_uuid_v4(), $branchId, $day,
                        $isOpen ? $slot1Start : null,
                        $isOpen ? $slot1End   : null,
                        $isOpen ? $slot2Start : null,
                        $isOpen ? $slot2End   : null,
                        $isOpen ? 0 : 1,
                        $actorId,
                    ]);
                }
            }
        }

        // --- mci_business_products: full replace ---
        if (in_array('products', $editSections, true)) {
        $pdo->prepare('DELETE FROM mci_business_products WHERE business_group_id = ?')->execute([$groupId]);
        $products = $data['products'] ?? [];
        if (is_array($products)) {
            $prodStmt = $pdo->prepare('
                INSERT INTO mci_business_products
                  (id, business_group_id, name, description,
                   price_min, price_max, price_unit, image_path,
                   sort_order, created_by_user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            foreach ($products as $i => $p) {
                $pName = trim((string)($p['name'] ?? ''));
                if ($pName === '') {
                    continue;
                }
                $pid = api_uuid_v4();
                $productIds[] = $pid;
                $prodStmt->execute([
                    $pid, $groupId,
                    $pName,
                    trim((string)($p['description'] ?? '')) ?: null,
                    is_numeric($p['price_min'] ?? '') ? (float)$p['price_min'] : null,
                    is_numeric($p['price_max'] ?? '') ? (float)$p['price_max'] : null,
                    trim((string)($p['price_unit'] ?? 'INR')) ?: 'INR',
                    trim((string)($p['image_path'] ?? '')) ?: null,
                    $i,
                    $actorId,
                ]);
            }
        }
        }

        // --- mci_business_services: full replace ---
        if (in_array('services', $editSections, true)) {
        $pdo->prepare('DELETE FROM mci_business_services WHERE business_group_id = ?')->execute([$groupId]);
        $services = $data['services'] ?? [];
        if (is_array($services)) {
            $svcStmt = $pdo->prepare('
                INSERT INTO mci_business_services
                  (id, business_group_id, name, description,
                   price_min, price_max, price_unit, image_path,
                   sort_order, created_by_user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            foreach ($services as $i => $s) {
                $sName = trim((string)($s['name'] ?? ''));
                if ($sName === '') {
                    continue;
                }
                $sid = api_uuid_v4();
                $serviceIds[] = $sid;
                $svcStmt->execute([
                    $sid, $groupId,
                    $sName,
                    trim((string)($s['description'] ?? '')) ?: null,
                    is_numeric($s['price_min'] ?? '') ? (float)$s['price_min'] : null,
                    is_numeric($s['price_max'] ?? '') ? (float)$s['price_max'] : null,
                    trim((string)($s['price_unit'] ?? 'INR')) ?: 'INR',
                    trim((string)($s['image_path'] ?? '')) ?: null,
                    $i,
                    $actorId,
                ]);
            }
        }
        }

        // --- mci_business_faqs: full replace ---
        if (in_array('faqs', $editSections, true) || in_array('core', $editSections, true)) {
        $pdo->prepare('DELETE FROM mci_business_faqs WHERE business_group_id = ?')->execute([$groupId]);
        $faqs = $data['faqs'] ?? [];
        if (is_array($faqs)) {
            $faqStmt = $pdo->prepare('
                INSERT INTO mci_business_faqs
                  (id, business_group_id, question, answer, sort_order, created_by_user_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            foreach ($faqs as $i => $f) {
                $q = trim((string)($f['question'] ?? ''));
                if ($q === '') {
                    continue;
                }
                $faqStmt->execute([
                    api_uuid_v4(), $groupId,
                    $q,
                    trim((string)($f['answer'] ?? '')),
                    $i,
                    $actorId,
                ]);
            }
        }
        }

        // --- mci_business_social_links: full replace ---
        if (in_array('social', $editSections, true) || in_array('location', $editSections, true)) {
        $pdo->prepare('DELETE FROM mci_business_social_links WHERE business_group_id = ?')->execute([$groupId]);
        $socialLinks = $branch['social_links'] ?? [];
        $platformMap = [
            'facebook'         => 'facebook',
            'instagram'        => 'instagram',
            'x'                => 'twitter',
            'youtube'          => 'youtube',
            'linkedin'         => 'linkedin',
            'tiktok'           => 'tiktok',
            'pinterest'        => 'pinterest',
            'telegram'         => 'telegram',
            'threads'          => 'threads',
            'snapchat'         => 'snapchat',
            'whatsapp_channel' => 'whatsapp_channel',
        ];
        if (is_array($socialLinks)) {
            $socStmt = $pdo->prepare('
                INSERT INTO mci_business_social_links
                  (id, business_group_id, platform, url, created_by_user_id)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE url=VALUES(url)
            ');
            foreach ($platformMap as $inputKey => $dbPlatform) {
                $url = trim((string)($socialLinks[$inputKey] ?? ''));
                if ($url !== '') {
                    $socStmt->execute([api_uuid_v4(), $groupId, $dbPlatform, $url, $actorId]);
                }
            }
        }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        if (function_exists('mci_log_error')) {
            mci_log_error('api_business_update', $e);
        } else {
            error_log('api_business_update: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
        return [
            'ok'     => false,
            'error'  => 'server_error',
            'detail' => get_class($e) . ': ' . $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine(),
            'status' => 500,
        ];
    }

    return [
        'ok'          => true,
        'id'          => $groupId,
        'product_ids' => $productIds,
        'service_ids' => $serviceIds,
    ];
}

// ---------------------------------------------------------------------------
// List — CP
// ---------------------------------------------------------------------------

/**
 * @param array{status?:string|null, added_by_role?:string|null, category_id?:int|null, q?:string|null, page?:int, per_page?:int} $filters
 */
function api_business_list_cp(PDO $pdo, array $filters = []): array
{
    $where  = ['g.status != \'deleted\''];
    $params = [];

    if (!empty($filters['status'])) {
        $where[]  = 'g.status = ?';
        $params[] = $filters['status'];
    }
    if (!empty($filters['added_by_role'])) {
        $where[]  = 'g.added_by_role = ?';
        $params[] = $filters['added_by_role'];
    }
    if (!empty($filters['category_id'])) {
        $where[]  = 'g.parent_category_id = ?';
        $params[] = (int)$filters['category_id'];
    }
    if (!empty($filters['q'])) {
        $where[]  = '(g.name LIKE ? OR g.slug LIKE ?)';
        $like     = '%' . $filters['q'] . '%';
        $params[] = $like;
        $params[] = $like;
    }

    $perPage = max(1, min(100, (int)($filters['per_page'] ?? 25)));
    $page    = max(1, (int)($filters['page'] ?? 1));
    $offset  = ($page - 1) * $perPage;

    $whereStr = implode(' AND ', $where);

    $countStmt = $pdo->prepare("SELECT COUNT(*) AS total FROM mci_business_groups g WHERE $whereStr");
    $countStmt->execute($params);
    $total = (int)($countStmt->fetch()['total'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT g.id, g.name, g.slug, g.status, g.added_by_role, g.added_by_user_id,
               g.logo_path, g.price_range, g.created_at,
               c.name AS category_name,
               (SELECT COUNT(*) FROM mci_business_branches WHERE business_group_id = g.id AND status != 'deleted') AS branch_count
        FROM mci_business_groups g
        LEFT JOIN mci_categories c ON g.parent_category_id = c.id
        WHERE $whereStr
        ORDER BY g.created_at DESC
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute($params);
    $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'businesses' => $businesses,
        'total'      => $total,
        'page'       => $page,
        'per_page'   => $perPage,
        'pages'      => (int)ceil($total / $perPage),
    ];
}

// ---------------------------------------------------------------------------
// List — Owner (subscriber)
// ---------------------------------------------------------------------------

function api_business_list_owner(PDO $pdo, string $userId): array
{
    $stmt = $pdo->prepare("
        SELECT g.id, g.name, g.slug, g.status, g.added_by_role, g.logo_path,
               g.price_range, g.created_at,
               c.name AS category_name,
               (SELECT COUNT(*) FROM mci_business_branches WHERE business_group_id = g.id AND status != 'deleted') AS branch_count
        FROM mci_business_groups g
        LEFT JOIN mci_categories c ON g.parent_category_id = c.id
        WHERE g.added_by_user_id = ? AND g.status != 'deleted'
        ORDER BY g.created_at DESC
    ");
    $stmt->execute([$userId]);
    return ['businesses' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
}

// ---------------------------------------------------------------------------
// Status update (approve / reject / suspend)
// ---------------------------------------------------------------------------

function api_business_update_status(
    PDO $pdo,
    string $groupId,
    string $newStatus,
    string $actorId,
    ?string $notes = null
): bool {
    $allowedStatuses = ['live', 'draft', 'suspended', 'deleted', 'rejected'];
    if (!in_array($newStatus, $allowedStatuses, true)) {
        return false;
    }

    $stmt = $pdo->prepare('SELECT status FROM mci_business_groups WHERE id = ? LIMIT 1');
    $stmt->execute([$groupId]);
    $row = $stmt->fetch();
    if (!$row) {
        return false;
    }
    $previousStatus = (string)$row['status'];

    $actionMap = [
        'live'      => 'approved',
        'rejected'  => 'rejected',
        'suspended' => 'suspended',
        'draft'     => 'draft',
        'deleted'   => 'rejected',
    ];
    $action = $actionMap[$newStatus] ?? 'approved';

    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE mci_business_groups SET status = ?, updated_by_user_id = ? WHERE id = ?')
            ->execute([$newStatus, $actorId, $groupId]);

        $pdo->prepare('
            INSERT INTO mci_business_approvals
              (id, business_group_id, action, previous_status, new_status,
               notes, reviewed_by_user_id, reviewed_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(6))
        ')->execute([
            api_uuid_v4(), $groupId,
            $action, $previousStatus, $newStatus,
            $notes,
            $actorId,
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('api_business_update_status error: ' . $e->getMessage());
        return false;
    }

    return true;
}

function api_business_flag_create(PDO $pdo, array $data, ?array $auth): array
{
    $businessGroupId = trim((string)($data['business_group_id'] ?? ''));
    $reason = trim((string)($data['reason'] ?? ''));
    $reporterName = trim((string)($data['reporter_name'] ?? ''));
    $reporterEmail = trim((string)($data['reporter_email'] ?? ''));
    $reporterType = trim((string)($data['reporter_type'] ?? 'anonymous'));

    if ($businessGroupId === '' || $reason === '') {
        return ['ok' => false, 'error' => 'missing_fields', 'status' => 400];
    }
    $allowedReporterTypes = ['logged_in', 'guest', 'anonymous'];
    if (!in_array($reporterType, $allowedReporterTypes, true)) {
        $reporterType = 'anonymous';
    }
    if ($auth !== null && !empty($auth['user_id'])) {
        $reporterType = 'logged_in';
    }
    if ($reporterType === 'guest' && $reporterName === '' && $reporterEmail === '') {
        $reporterType = 'anonymous';
    }

    $existsStmt = $pdo->prepare("SELECT id FROM mci_business_groups WHERE id = ? LIMIT 1");
    $existsStmt->execute([$businessGroupId]);
    if (!$existsStmt->fetchColumn()) {
        return ['ok' => false, 'error' => 'business_not_found', 'status' => 404];
    }

    $id = api_uuid_v4();
    $pdo->prepare("
        INSERT INTO mci_business_flags
          (id, business_group_id, reporter_user_id, reporter_type, reporter_name, reporter_email, reason, status, created_by_user_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'open', ?)
    ")->execute([
        $id,
        $businessGroupId,
        ($auth !== null && !empty($auth['user_id'])) ? (string)$auth['user_id'] : null,
        $reporterType,
        $reporterName !== '' ? $reporterName : null,
        $reporterEmail !== '' ? $reporterEmail : null,
        mb_substr($reason, 0, 4000),
        ($auth !== null && !empty($auth['user_id'])) ? (string)$auth['user_id'] : null,
    ]);

    return ['ok' => true, 'id' => $id];
}

function api_business_flag_list_cp(PDO $pdo, int $page = 1, int $perPage = 25, string $status = 'open'): array
{
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));
    $offset = ($page - 1) * $perPage;
    $status = trim($status);

    $params = [];
    $where = '1=1';
    if ($status !== '' && $status !== 'all') {
        $where = 'f.status = ?';
        $params[] = $status;
    }

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM mci_business_flags f WHERE {$where}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT
          f.*,
          g.name AS business_name,
          g.slug AS business_slug,
          owner.id AS owner_user_id,
          COALESCE(NULLIF(TRIM(owner.display_name), ''), owner.email) AS owner_display_name
        FROM mci_business_flags f
        INNER JOIN mci_business_groups g ON g.id = f.business_group_id
        LEFT JOIN mci_users owner ON owner.id = g.added_by_user_id
        WHERE {$where}
        ORDER BY f.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return [
        'flags' => $rows,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'pages' => $total > 0 ? (int)ceil($total / $perPage) : 1,
    ];
}

function api_business_flag_resolve(PDO $pdo, string $flagId, string $status, string $actorId, ?string $adminNote = null): bool
{
    if (!in_array($status, ['resolved', 'dismissed'], true)) {
        return false;
    }
    $stmt = $pdo->prepare("
        UPDATE mci_business_flags
        SET status = ?, admin_note = ?, resolved_by_user_id = ?, resolved_at = NOW(6), updated_by_user_id = ?
        WHERE id = ? AND status = 'open'
    ");
    $stmt->execute([$status, $adminNote, $actorId, $actorId, $flagId]);
    return $stmt->rowCount() > 0;
}

// ---------------------------------------------------------------------------
// Public listing functions (status=live only)
// ---------------------------------------------------------------------------

/**
 * List live businesses for public pages.
 *
 * Filters (all optional):
 *   category_id   int     — exact parent_category_id match
 *   category_slug string  — match mci_categories.slug (parent category)
 *   q             string  — LIKE match on business name
 *   city          string  — LIKE match on branch city
 *   price_range   string  — exact enum value
 *   tag_slug         string  — business must have this tag slug
 *   subcategory_slug string  — business must belong to this subcategory slug
 *   page          int     — default 1
 *   per_page      int     — default 12, max 100
 *   sort          string  — 'newest' (default) | 'oldest'
 *
 * Returns:
 *   ['businesses' => [...], 'total' => int, 'page' => int, 'per_page' => int, 'pages' => int]
 *
 * Each row: id, name, slug, tagline, logo_path, banner_path, price_range,
 *           created_at, category_name, category_slug, city, phone_primary,
 *           latitude, longitude
 */
function api_business_list_public(PDO $pdo, array $filters = []): array
{
    $page    = max(1, (int)($filters['page']     ?? 1));
    $perPage = min(100, max(1, (int)($filters['per_page'] ?? 12)));
    $offset  = ($page - 1) * $perPage;
    $sort    = (($filters['sort'] ?? 'newest') === 'oldest') ? 'ASC' : 'DESC';

    $where  = ["g.status = 'live'"];
    $params = [];

    if (!empty($filters['category_id'])) {
        $where[]                 = 'g.parent_category_id = :category_id';
        $params[':category_id']  = (int)$filters['category_id'];
    }
    if (!empty($filters['category_slug'])) {
        $where[]                   = 'c.slug = :category_slug';
        $params[':category_slug']  = (string)$filters['category_slug'];
    }
    if (!empty($filters['q'])) {
        $where[]        = 'g.name LIKE :q';
        $params[':q']   = '%' . $filters['q'] . '%';
    }
    if (!empty($filters['city'])) {
        $where[]          = 'b.city LIKE :city';
        $params[':city']  = '%' . $filters['city'] . '%';
    }
    if (!empty($filters['price_range'])) {
        $where[]                  = 'g.price_range = :price_range';
        $params[':price_range']   = (string)$filters['price_range'];
    }
    if (!empty($filters['tag_slug'])) {
        $where[] = 'EXISTS (
            SELECT 1 FROM mci_business_tags bt
            JOIN mci_tags t ON t.id = bt.tag_id
            WHERE bt.business_group_id = g.id AND t.slug = :tag_slug
        )';
        $params[':tag_slug'] = (string)$filters['tag_slug'];
    }
    if (!empty($filters['subcategory_slug'])) {
        $where[] = 'EXISTS (
            SELECT 1 FROM mci_business_subcategories bsc
            JOIN mci_categories sc ON sc.id = bsc.category_id
            WHERE bsc.business_group_id = g.id AND sc.slug = :subcategory_slug
        )';
        $params[':subcategory_slug'] = (string)$filters['subcategory_slug'];
    }

    $whereClause = implode(' AND ', $where);

    $baseJoins = '
        FROM mci_business_groups g
        LEFT JOIN mci_categories c ON c.id = g.parent_category_id
        LEFT JOIN mci_business_branches b
               ON b.business_group_id = g.id
              AND b.id = (
                    SELECT b2.id FROM mci_business_branches b2
                    WHERE b2.business_group_id = g.id
                    ORDER BY b2.is_primary DESC, b2.created_at ASC
                    LIMIT 1
                  )
    ';

    $countStmt = $pdo->prepare("SELECT COUNT(DISTINCT g.id) $baseJoins WHERE $whereClause");
    $countStmt->execute($params);
    $total = (int)($countStmt->fetchColumn() ?? 0);
    $pages = $total > 0 ? (int)ceil($total / $perPage) : 1;

    $dataStmt = $pdo->prepare("
        SELECT
            g.id, g.name, g.slug, g.tagline,
            g.logo_path, g.banner_path,
            g.price_range, g.created_at,
            c.name AS category_name,
            c.slug AS category_slug,
            b.city,
            b.phone_primary,
            b.latitude,
            b.longitude
        $baseJoins
        WHERE $whereClause
        ORDER BY g.created_at $sort
        LIMIT :limit OFFSET :offset
    ");
    foreach ($params as $k => $v) {
        $dataStmt->bindValue($k, $v);
    }
    $dataStmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $dataStmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $dataStmt->execute();

    return [
        'businesses' => $dataStmt->fetchAll(PDO::FETCH_ASSOC) ?: [],
        'total'      => $total,
        'page'       => $page,
        'per_page'   => $perPage,
        'pages'      => $pages,
    ];
}

/**
 * Fetch a single live business by its slug (full denormalised record).
 * Returns null if not found or not live.
 */
function api_business_fetch_by_slug(PDO $pdo, string $slug): ?array
{
    $stmt = $pdo->prepare("SELECT id FROM mci_business_groups WHERE slug = ? AND status = 'live' LIMIT 1");
    $stmt->execute([$slug]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }
    return api_business_fetch($pdo, (string)$row['id']);
}

// ---------------------------------------------------------------------------
// Patch images — save uploaded image paths after business creation
// ---------------------------------------------------------------------------

/**
 * Save uploaded image paths to a business group.
 *
 * $images keys (all optional):
 *   logo_path       string
 *   profile_path    string
 *   banner_path     string
 *   gallery_paths   string[]
 *   product_images  array of {id: string, path: string}
 *   service_images  array of {id: string, path: string}
 *
 * All paths validated to start with /storage/uploads/.
 * Returns ['ok'=>true] or ['ok'=>false, 'error'=>string, 'status'=>int]
 */
function api_business_patch_images(PDO $pdo, string $groupId, array $images, string $actorId): array
{
    // Verify group exists
    $stmt = $pdo->prepare('SELECT id FROM mci_business_groups WHERE id = ? LIMIT 1');
    $stmt->execute([$groupId]);
    if (!$stmt->fetch()) {
        return ['ok' => false, 'error' => 'business_not_found', 'status' => 404];
    }

    // Path safety: under /storage/uploads/, no traversal or encoded tricks
    $safePath = function (mixed $v): ?string {
        $s = trim((string) $v);
        if ($s === '' || !str_starts_with($s, '/storage/uploads/')) {
            return null;
        }
        if (str_contains($s, '..') || str_contains($s, "\0")) {
            return null;
        }
        if (preg_match('#//#', $s) === 1) {
            return null;
        }

        return $s;
    };

    $logoPath    = $safePath($images['logo_path']    ?? '');
    $profilePath = $safePath($images['profile_path'] ?? '');
    $bannerPath  = $safePath($images['banner_path']  ?? '');
    $galleryPaths  = array_values(array_filter(
        array_map(fn($p) => $safePath($p), (array)($images['gallery_paths'] ?? []))
    ));
    $productImages = (array)($images['product_images'] ?? []);
    $serviceImages = (array)($images['service_images'] ?? []);

    $pdo->beginTransaction();
    try {
        // Update group: only set columns where a valid path was provided
        $setClauses = [];
        $setParams  = [];
        if ($logoPath !== null)    { $setClauses[] = 'logo_path = ?';    $setParams[] = $logoPath; }
        if ($profilePath !== null) { $setClauses[] = 'profile_path = ?'; $setParams[] = $profilePath; }
        if ($bannerPath !== null)  { $setClauses[] = 'banner_path = ?';  $setParams[] = $bannerPath; }
        if ($setClauses !== []) {
            $setParams[] = $groupId;
            $pdo->prepare('UPDATE mci_business_groups SET ' . implode(', ', $setClauses) . ' WHERE id = ?')
                ->execute($setParams);
        }

        // Gallery: INSERT using file_path column (NOT image_path — schema uses file_path)
        if ($galleryPaths !== []) {
            $imgStmt = $pdo->prepare('
                INSERT INTO mci_business_images
                  (id, business_group_id, file_path, sort_order,
                   uploaded_by_user_id, created_by_user_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            foreach ($galleryPaths as $i => $path) {
                $imgStmt->execute([api_uuid_v4(), $groupId, $path, $i, $actorId, $actorId]);
            }
        }

        // Products: match by row id + business_group_id for safety
        // Note: mci_business_products uses image_path column (not file_path)
        foreach ($productImages as $entry) {
            $rowId = trim((string)($entry['id'] ?? ''));
            $path  = $safePath($entry['path'] ?? '');
            if ($rowId === '' || $path === null) {
                continue;
            }
            $pdo->prepare('UPDATE mci_business_products SET image_path = ? WHERE id = ? AND business_group_id = ?')
                ->execute([$path, $rowId, $groupId]);
        }

        // Services: same pattern
        foreach ($serviceImages as $entry) {
            $rowId = trim((string)($entry['id'] ?? ''));
            $path  = $safePath($entry['path'] ?? '');
            if ($rowId === '' || $path === null) {
                continue;
            }
            $pdo->prepare('UPDATE mci_business_services SET image_path = ? WHERE id = ? AND business_group_id = ?')
                ->execute([$path, $rowId, $groupId]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('api_business_patch_images error: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'server_error', 'status' => 500];
    }

    return ['ok' => true];
}
