<?php
/**
 * Shared data-loading logic for all CP listings sub-pages.
 *
 * Caller must define:
 *   $statusFilter   string|null  — DB status value or null for all
 *   $roleFilter     string|null  — DB added_by_role value or null for all
 *   $pageBase       string       — base URL for pagination (trailing slash)
 *
 * Outputs (sets these vars):
 *   $rows, $total, $pages, $curPage, $q, $flash, $counts
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/mci_config.php';
require_once __DIR__ . '/../../includes/mci_session.php';
require_once __DIR__ . '/../../includes/mci_require_session.php';
require_once __DIR__ . '/../../includes/mci_csrf.php';
require_once __DIR__ . '/../../api/v1/lib/db.php';
require_once __DIR__ . '/../../api/v1/lib/business_service.php';

mci_require_cp_session();

$flash   = '';
$q       = trim((string)($_GET['q'] ?? ''));
$curPage = max(1, (int)($_GET['page'] ?? 1));

$dbFilters = [
    'page'     => $curPage,
    'per_page' => MCI_CP_LISTING_PER_PAGE,
];
if (!empty($statusFilter)) {
    $dbFilters['status'] = $statusFilter;
}
if (!empty($roleFilter)) {
    $dbFilters['added_by_role'] = $roleFilter;
}
if ($q !== '') {
    $dbFilters['q'] = $q;
}

try {
    $result = api_business_list_cp(api_db(), $dbFilters);
    $rows   = $result['businesses'] ?? [];
    $total  = $result['total'] ?? 0;
    $pages  = $result['pages'] ?? 1;
} catch (Throwable $e) {
    $rows  = [];
    $total = 0;
    $pages = 1;
    $flash = 'error:Could not load listings: ' . $e->getMessage();
}

// ── Badge counts for the sidebar sub-nav ─────────────────
$counts = [
    'all'        => 0,
    'draft'      => 0,
    'live'       => 0,
    'rejected'   => 0,
    'suspended'  => 0,
    'anonymous'  => 0,
    'cp_admin'   => 0,
];
try {
    $pdo = api_db();
    foreach (['draft','live','rejected','suspended'] as $s) {
        $st = $pdo->prepare("SELECT COUNT(*) FROM mci_business_groups WHERE status = ? AND status != 'deleted'");
        $st->execute([$s]);
        $counts[$s] = (int)($st->fetchColumn() ?? 0);
    }
    $st = $pdo->query("SELECT COUNT(*) FROM mci_business_groups WHERE added_by_role = 'anonymous' AND status != 'deleted'");
    $counts['anonymous'] = (int)($st ? $st->fetchColumn() : 0);
    $st = $pdo->query("SELECT COUNT(*) FROM mci_business_groups WHERE added_by_role = 'cp_admin' AND status != 'deleted'");
    $counts['cp_admin'] = (int)($st ? $st->fetchColumn() : 0);
    $counts['all'] = $counts['draft'] + $counts['live'] + $counts['rejected'] + $counts['suspended'];
} catch (Throwable $ignored) {}
