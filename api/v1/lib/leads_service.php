<?php

declare(strict_types=1);

/**
 * Leads & Enquiries service — mci_leads table.
 *
 * A "lead"    has type='lead'    (phone + email + message from the business contact form).
 * An "enquiry" has type='enquiry' (email + message from a quick-contact widget).
 *
 * All functions scope to business groups owned by the requesting subscriber
 * so one subscriber can never see another's data.
 */

// ---------------------------------------------------------------------------
// Read
// ---------------------------------------------------------------------------

/**
 * Return leads or enquiries belonging to the subscriber's business groups.
 *
 * @param string   $userId        Logged-in subscriber's mci_users.id
 * @param string   $type          'lead' | 'enquiry'
 * @param string   $statusFilter  'all' | 'new' | 'contacted' | 'converted' | 'closed' | 'replied'
 * @param string   $businessSearch  Partial match on business name (empty = no filter)
 * @param string   $fromDate      Y-m-d or ''
 * @param string   $toDate        Y-m-d or ''
 * @return array{items: list<array>, totals: array{all:int,new:int,contacted:int,converted:int,closed:int,replied:int}}
 */
function leads_list(PDO $pdo, string $userId, string $type, string $statusFilter = 'all', string $businessSearch = '', string $fromDate = '', string $toDate = ''): array
{
    $emptyTotals = ['all' => 0, 'new' => 0, 'contacted' => 0, 'converted' => 0, 'closed' => 0, 'replied' => 0];

    if ($userId === '') {
        return ['items' => [], 'totals' => $emptyTotals];
    }

    $type = $type === 'enquiry' ? 'enquiry' : 'lead';

    // ── Build WHERE ───────────────────────────────────────────────────────────
    // Scope to business groups the subscriber owns.
    $where  = ['l.type = ?', 'g.added_by_user_id = ?'];
    $binds  = [$type, $userId];

    $validStatuses = ['new', 'contacted', 'converted', 'closed', 'replied'];
    if ($statusFilter !== 'all' && in_array($statusFilter, $validStatuses, true)) {
        $where[] = 'l.status = ?';
        $binds[] = $statusFilter;
    }

    if ($businessSearch !== '') {
        $where[] = 'g.name LIKE ?';
        $binds[] = '%' . $businessSearch . '%';
    }

    if ($fromDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
        $where[] = 'DATE(l.created_at) >= ?';
        $binds[] = $fromDate;
    }

    if ($toDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
        $where[] = 'DATE(l.created_at) <= ?';
        $binds[] = $toDate;
    }

    $whereSql = implode(' AND ', $where);

    // ── Totals (unfiltered by status/date/business so counts are always global) ─
    $totalsStmt = $pdo->prepare(
        "SELECT l.status, COUNT(*) AS cnt
         FROM mci_leads l
         INNER JOIN mci_business_groups g ON g.id = l.business_group_id
         WHERE l.type = ? AND g.added_by_user_id = ?
         GROUP BY l.status"
    );
    $totalsStmt->execute([$type, $userId]);
    $totals = $emptyTotals;
    foreach ($totalsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $key = strtolower((string)$row['status']);
        if (isset($totals[$key])) {
            $totals[$key] = (int)$row['cnt'];
        }
        $totals['all'] += (int)$row['cnt'];
    }

    // ── Items ─────────────────────────────────────────────────────────────────
    $stmt = $pdo->prepare(
        "SELECT
            l.id,
            l.sender_name,
            l.sender_phone,
            l.sender_email,
            l.message,
            l.status,
            l.created_at,
            g.name AS business_name,
            g.slug AS business_slug
         FROM mci_leads l
         INNER JOIN mci_business_groups g ON g.id = l.business_group_id
         WHERE {$whereSql}
         ORDER BY l.created_at DESC"
    );
    $stmt->execute($binds);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    foreach ($rows as $row) {
        $items[] = [
            'id'            => (string)$row['id'],
            'listing'       => (string)$row['business_name'],
            'business_slug' => (string)$row['business_slug'],
            'name'          => (string)$row['sender_name'],
            'phone'         => (string)$row['sender_phone'],
            'email'         => (string)$row['sender_email'],
            'message'       => (string)$row['message'],
            'date'          => substr((string)$row['created_at'], 0, 10),
            'when'          => leads_relative_time((string)$row['created_at']),
            'status'        => (string)$row['status'],
        ];
    }

    return ['items' => $items, 'totals' => $totals];
}

/**
 * Update the status of a lead/enquiry, scoped to the subscriber's own businesses.
 * Returns true on success, false if not found / not authorised.
 */
function leads_update_status(PDO $pdo, string $leadId, string $newStatus, string $userId): bool
{
    $validStatuses = ['new', 'contacted', 'converted', 'closed', 'replied'];
    if ($leadId === '' || $userId === '' || !in_array($newStatus, $validStatuses, true)) {
        return false;
    }

    $stmt = $pdo->prepare(
        "UPDATE mci_leads l
         INNER JOIN mci_business_groups g ON g.id = l.business_group_id
         SET l.status = ?, l.updated_at = NOW()
         WHERE l.id = ? AND g.added_by_user_id = ?"
    );
    $stmt->execute([$newStatus, $leadId, $userId]);
    return $stmt->rowCount() > 0;
}

/**
 * Insert a new lead/enquiry row. Called from the public contact form on business pages.
 * Returns the new UUID on success or '' on failure.
 */
function leads_create(PDO $pdo, string $businessGroupId, string $type, string $senderName, string $senderPhone, string $senderEmail, string $message): string
{
    if ($businessGroupId === '' || $message === '') {
        return '';
    }
    $type = $type === 'enquiry' ? 'enquiry' : 'lead';

    require_once __DIR__ . '/uuid.php';
    $id  = api_uuid_v4();
    $now = gmdate('Y-m-d H:i:s');

    $stmt = $pdo->prepare(
        "INSERT INTO mci_leads
            (id, business_group_id, type, sender_name, sender_phone, sender_email, message, status, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'new', ?, ?)"
    );
    $stmt->execute([
        $id, $businessGroupId, $type,
        substr($senderName,  0, 160),
        substr($senderPhone, 0, 40),
        substr($senderEmail, 0, 254),
        $message,
        $now, $now,
    ]);
    return $stmt->rowCount() > 0 ? $id : '';
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function leads_relative_time(string $datetime): string
{
    $ts   = strtotime($datetime);
    if ($ts === false) {
        return $datetime;
    }
    $diff = time() - $ts;
    if ($diff < 3600)        return 'Just now';
    if ($diff < 86400)       return (int)($diff / 3600) . ' hour' . ((int)($diff / 3600) !== 1 ? 's' : '') . ' ago';
    if ($diff < 172800)      return 'Yesterday';
    if ($diff < 604800)      return (int)($diff / 86400) . ' days ago';
    if ($diff < 1209600)     return '1 week ago';
    return date('M j, Y', $ts);
}
