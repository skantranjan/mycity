<?php

declare(strict_types=1);

/**
 * Redirect to login if the visitor is not a logged-in subscriber (session + role).
 */
function mci_require_subscriber_session(): void
{
    $role = (string)($_SESSION['mci_role'] ?? '');
    if (empty($_SESSION['mci_logged_in']) || $role !== 'subscriber' || empty($_SESSION['mci_user_id'])) {
        $ret = (string)($_SERVER['REQUEST_URI'] ?? '/subscriber/profile/');
        header('Location: /login/?return=' . rawurlencode($ret));
        exit;
    }
}

/**
 * Redirect to CP login if not a control-panel user.
 */
function mci_require_cp_session(): void
{
    $role = (string)($_SESSION['mci_cp_role'] ?? '');
    if (empty($_SESSION['mci_cp_logged_in']) || empty($_SESSION['mci_cp_user_id'] ?? null)) {
        $ret = (string)($_SERVER['REQUEST_URI'] ?? '/cp/profile/');
        header('Location: /cp/login/?return=' . rawurlencode($ret));
        exit;
    }
    if (!in_array($role, ['super_admin', 'co_admin'], true)) {
        header('Location: /cp/login/?return=' . rawurlencode((string)($_SERVER['REQUEST_URI'] ?? '/cp/profile/')));
        exit;
    }
}

/**
 * Redirect unless the CP session is a super admin (not co-admin).
 */
function mci_require_super_admin_session(): void
{
    $role = (string) ($_SESSION['mci_cp_role'] ?? '');
    if (empty($_SESSION['mci_cp_logged_in']) || empty($_SESSION['mci_cp_user_id'] ?? null)) {
        $ret = (string) ($_SERVER['REQUEST_URI'] ?? '/cp/dashboard/');
        header('Location: /cp/login/?return=' . rawurlencode($ret));
        exit;
    }
    if ($role !== 'super_admin') {
        header('Location: /cp/dashboard/?notice=forbidden');
        exit;
    }
}
