<?php
declare(strict_types=1);

/**
 * Ensures a valid JWT cookie exists for the current CP session.
 *
 * Call this after mci_require_cp_session() on any CP page that needs to
 * inject the JWT into JavaScript for API fetch() calls.
 *
 * - If the cookie is present and not expired, returns the existing token.
 * - If missing or expired, issues a fresh 8-hour token from session data
 *   and sets a new cookie so subsequent page loads also benefit.
 *
 * @return string  The JWT (never empty when called after a valid CP session)
 */
function mci_cp_ensure_jwt(): string
{
    // Already have a valid cookie?
    $existing = $_COOKIE['mci_api_token'] ?? '';
    if ($existing !== '') {
        // Quick expiry check without full verification
        $parts = explode('.', $existing);
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            if (is_array($payload) && isset($payload['exp']) && (int)$payload['exp'] > time() + 60) {
                return $existing;
            }
        }
    }

    // Re-issue from session
    $userId = (string)($_SESSION['mci_cp_user_id'] ?? '');
    $role   = (string)($_SESSION['mci_cp_role']    ?? '');
    if ($userId === '' || $role === '') {
        return '';
    }

    $exp = time() + 28800; // 8 hours
    $jwt = api_jwt_sign(['sub' => $userId, 'role' => $role, 'iat' => time(), 'exp' => $exp]);

    // Write the new cookie
    setcookie('mci_api_token', $jwt, [
        'expires'  => $exp,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    return $jwt;
}
