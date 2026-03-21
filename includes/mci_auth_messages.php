<?php

declare(strict_types=1);

/**
 * Human-readable messages for api_direct_* / API error codes (auth flows).
 */
function mci_auth_error_message(string $code): string
{
    static $map = [
        'invalid_credentials' => 'Invalid email or password.',
        'email_and_password_required' => 'Email and password are required.',
        'invalid_email' => 'Please enter a valid email address.',
        'password_too_short' => 'Password must be at least 8 characters.',
        'terms_and_privacy_required' => 'You must accept the Terms of Use and Privacy Policy.',
        'email_already_registered' => 'That email is already registered.',
        'server_config' => 'Login is not available: check database env vars (MCI_DB_*) and MCI_JWT_SECRET on the server.',
        'invalid_server_bootstrap' => 'Server bootstrap failed.',
        'login_failed' => 'Sign in failed. Try again.',
        'register_failed' => 'Registration failed. Try again.',
    ];

    return $map[$code] ?? $code;
}
