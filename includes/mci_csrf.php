<?php
declare(strict_types=1);

require_once __DIR__ . '/mci_session.php';

/**
 * Minimal CSRF protection helper.
 * - Stores per-action CSRF token in session.
 * - Generates a hidden form input with token.
 * - Verifies token equality using hash_equals.
 */

function mci_csrf_token(string $action = 'default'): string
{
    $action = trim($action);
    if ($action === '') {
        $action = 'default';
    }
    if (!isset($_SESSION['mci_csrf'][$action]) || !is_string($_SESSION['mci_csrf'][$action])) {
        $_SESSION['mci_csrf'][$action] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['mci_csrf'][$action];
}

function mci_csrf_verify(string $action = 'default', string $token = ''): bool
{
    $action = trim($action);
    if ($action === '') {
        $action = 'default';
    }
    $expected = $_SESSION['mci_csrf'][$action] ?? '';
    if (!is_string($expected) || $expected === '') {
        return false;
    }
    if ($token === '') {
        return false;
    }
    return hash_equals($expected, $token);
}

function mci_csrf_input(string $action = 'default'): string
{
    $token = mci_csrf_token($action);
    $safeToken = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
    $safeAction = htmlspecialchars($action, ENT_QUOTES, 'UTF-8');
    // Keeping action hidden makes debugging easier; verification uses $action on server side.
    return '<input type="hidden" name="csrf_token" value="' . $safeToken . '" />' .
        '<input type="hidden" name="csrf_action" value="' . $safeAction . '" />';
}

