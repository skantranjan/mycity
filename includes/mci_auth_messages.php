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
        'invalid_audience' => 'Invalid login type. Use subscriber or control panel login only.',
        'audience_required' => 'Login type (audience) is required.',
        'invalid_current_password' => 'Current password is incorrect.',
        'password_mismatch' => 'New password and confirmation do not match.',
        'invalid_or_expired_token' => 'This reset link is invalid or has expired. Request a new one.',
        'token_required' => 'Reset token is required.',
        'reset_failed' => 'Could not reset password. Try again later.',
        'not_linked' => 'That sign-in method is not linked to your account.',
        'social_link_disabled' => 'Linking social accounts from this screen is disabled on this server.',
        'provider_user_id_required' => 'Provider user id is required to link an account.',
        'already_linked_or_conflict' => 'That provider account is already linked to another user.',
        'invalid_provider' => 'Unsupported sign-in provider.',
        'profile_error' => 'Could not update profile. Try again.',
        'invalid_timezone' => 'Please choose a valid timezone from the list.',
        'user_not_found' => 'Account not found.',
    ];

    return $map[$code] ?? $code;
}
