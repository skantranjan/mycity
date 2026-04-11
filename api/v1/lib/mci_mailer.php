<?php

declare(strict_types=1);

/**
 * Transactional email via SMTP (Hostinger, Brevo, etc.) using PHPMailer.
 *
 * Requires: composer install (phpmailer/phpmailer). Env: see .env.example.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/public_url.php';

use PHPMailer\PHPMailer\PHPMailer;

function mci_mail_h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function mci_mail_contact_email(): string
{
    $v = api_env_optional('MCI_CONTACT_EMAIL');

    return ($v !== null && $v !== '') ? $v : 'hello@mycityinfo.com';
}

function mci_mail_autoload(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;
    $autoload = dirname(__DIR__, 3) . '/vendor/autoload.php';
    if (is_readable($autoload)) {
        require_once $autoload;
    }
}

function mci_mail_is_configured(): bool
{
    if (!api_env_flag('MCI_MAIL_ENABLED')) {
        return false;
    }
    mci_mail_autoload();
    if (!class_exists(PHPMailer::class)) {
        return false;
    }
    $host = api_env_optional('MCI_SMTP_HOST');
    $user = api_env_optional('MCI_SMTP_USER');
    $pass = api_env_optional('MCI_SMTP_PASS');
    $from = api_env_optional('MCI_MAIL_FROM');

    return $host !== null && $host !== ''
        && $user !== null && $user !== ''
        && $pass !== null && $pass !== ''
        && $from !== null && $from !== '';
}

/**
 * @throws \PHPMailer\PHPMailer\Exception
 */
function mci_mail_create_transport(): PHPMailer
{
    mci_mail_autoload();
    if (!class_exists(PHPMailer::class)) {
        throw new RuntimeException('PHPMailer not available; run composer install.');
    }

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->CharSet = PHPMailer::CHARSET_UTF8;

    $host = api_env_optional('MCI_SMTP_HOST') ?? '';
    $mail->Host       = $host;
    $mail->SMTPAuth   = true;
    $mail->Username   = api_env_optional('MCI_SMTP_USER') ?? '';
    $mail->Password   = api_env_optional('MCI_SMTP_PASS') ?? '';

    $portStr = api_env_optional('MCI_SMTP_PORT');
    $port    = ($portStr !== null && $portStr !== '' && ctype_digit($portStr)) ? (int) $portStr : 465;
    $mail->Port = $port;

    $enc = strtolower(trim((string) (api_env_optional('MCI_SMTP_ENCRYPTION') ?? '')));
    if ($enc === '' || $enc === 'auto') {
        $enc = $port === 587 ? 'tls' : 'ssl';
    }
    if ($enc === 'tls' || $enc === 'starttls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } elseif ($enc === 'none' || $enc === 'off') {
        $mail->SMTPAuth   = false;
        $mail->SMTPSecure = '';
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    }

    $from     = api_env_optional('MCI_MAIL_FROM') ?? '';
    $fromName = api_env_optional('MCI_MAIL_FROM_NAME') ?? 'My City Info';
    $mail->setFrom($from, $fromName);

    $reply = api_env_optional('MCI_MAIL_REPLY_TO');
    if ($reply !== null && $reply !== '' && filter_var($reply, FILTER_VALIDATE_EMAIL)) {
        $mail->addReplyTo($reply);
    }

    $mail->Timeout = 20;

    return $mail;
}

/**
 * @param callable(PHPMailer):void $fn
 */
function mci_mail_try_send(callable $fn): void
{
    if (!mci_mail_is_configured()) {
        return;
    }
    try {
        $mail = mci_mail_create_transport();
        $fn($mail);
        $mail->send();
    } catch (Throwable $e) {
        error_log('mci_mail send failed: ' . $e->getMessage());
    }
}

function mci_mail_send_welcome(string $toEmail, ?string $displayName, bool $oauthVariant = false): void
{
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $base    = api_public_site_base_url();
    $contact = mci_mail_contact_email();
    $greet   = ($displayName !== null && trim($displayName) !== '')
        ? 'Hi ' . trim($displayName) . ','
        : 'Hi,';

    if ($oauthVariant) {
        $introPlain = "Thanks for joining My City Info. Your account is linked to social sign-in, so you don't need a separate password to sign in next time—just use the same provider button on the login page.";
        $introHtml  = '<p>Thanks for joining My City Info. Your account is linked to social sign-in, so you don’t need a separate password to sign in next time—just use the same provider button on the login page.</p>';
    } else {
        $introPlain = 'Thanks for creating a My City Info subscriber account. You can explore local businesses, save favourites, and manage your listings from your dashboard.';
        $introHtml  = '<p>Thanks for creating a My City Info subscriber account. You can explore local businesses, save favourites, and manage your listings from your dashboard.</p>';
    }

    $dash = $base . '/subscriber/dashboard/';
    $login = $base . '/login/';

    $plain = $greet . "\n\n"
        . $introPlain . "\n\n"
        . "Open your dashboard: {$dash}\n"
        . "Sign in any time: {$login}\n\n"
        . "If you didn’t create this account, contact us at {$contact} and we’ll help.\n\n"
        . "— My City Info\n"
        . $base . "/\n";

    $html = '<div style="max-width:560px;font-family:system-ui,Segoe UI,sans-serif;font-size:15px;line-height:1.5;color:#222;">'
        . '<h1 style="font-size:20px;margin:0 0 12px;">Welcome to My City Info</h1>'
        . '<p style="margin:0 0 8px;">' . mci_mail_h($greet) . '</p>'
        . $introHtml
        . '<p style="margin:20px 0;"><a href="' . mci_mail_h($dash) . '" style="display:inline-block;padding:10px 18px;background:#111;color:#fff;text-decoration:none;border-radius:6px;">Go to dashboard</a></p>'
        . '<p style="margin:0 0 16px;"><a href="' . mci_mail_h($login) . '">Sign in</a></p>'
        . '<p style="font-size:13px;color:#666;">If you didn’t create this account, contact us at <a href="mailto:' . mci_mail_h($contact) . '">' . mci_mail_h($contact) . '</a>.</p>'
        . '<p style="font-size:13px;color:#666;">— My City Info<br><a href="' . mci_mail_h($base . '/') . '">' . mci_mail_h($base) . '/</a></p>'
        . '</div>';

    mci_mail_try_send(static function (PHPMailer $mail) use ($toEmail, $plain, $html): void {
        $mail->addAddress($toEmail);
        $mail->Subject = 'Welcome to My City Info';
        $mail->Body    = $html;
        $mail->AltBody = $plain;
        $mail->isHTML(true);
    });
}

/**
 * CP / super-admin created user with password (subscriber, co_admin, super_admin).
 */
function mci_mail_send_account_invited(string $toEmail, ?string $displayName, string $roleShort): void
{
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $base    = api_public_site_base_url();
    $contact = mci_mail_contact_email();
    $greet   = ($displayName !== null && trim($displayName) !== '')
        ? 'Hi ' . trim($displayName) . ','
        : 'Hi,';

    $roleShort = strtolower(trim($roleShort));
    $isCpRole  = in_array($roleShort, ['co_admin', 'super_admin'], true);
    $cpLogin   = $base . '/cp/login/';
    $cpChg     = $base . '/cp/change-password/';
    $subLogin  = $base . '/login/';
    $dash      = $base . '/subscriber/dashboard/';

    $plain = $greet . "\n\n"
        . "A My City Info administrator created an account for {$toEmail}.\n"
        . "Use the password you were given securely to sign in. For security, consider changing your password after first sign-in.\n\n";
    if ($isCpRole) {
        $plain .= "Control panel sign-in: {$cpLogin}\nChange password (after login): {$cpChg}\n\n";
    } else {
        $plain .= "Sign in: {$subLogin}\nYour dashboard: {$dash}\n\n";
    }
    $plain .= "Questions? {$contact}\n\n— My City Info\n";

    $html = '<div style="max-width:560px;font-family:system-ui,Segoe UI,sans-serif;font-size:15px;line-height:1.5;color:#222;">'
        . '<h1 style="font-size:20px;margin:0 0 12px;">Your My City Info account</h1>'
        . '<p style="margin:0 0 8px;">' . mci_mail_h($greet) . '</p>'
        . '<p>A My City Info administrator created an account for <strong>' . mci_mail_h($toEmail) . '</strong>. '
        . 'Use the password you were given securely to sign in. Consider changing your password after first sign-in.</p>';

    if ($isCpRole) {
        $html .= '<p style="margin:20px 0;"><a href="' . mci_mail_h($cpLogin) . '" style="display:inline-block;padding:10px 18px;background:#111;color:#fff;text-decoration:none;border-radius:6px;">Control panel sign-in</a></p>'
            . '<p><a href="' . mci_mail_h($cpChg) . '">Change password</a> (after you sign in)</p>';
    } else {
        $html .= '<p style="margin:20px 0;"><a href="' . mci_mail_h($subLogin) . '" style="display:inline-block;padding:10px 18px;background:#111;color:#fff;text-decoration:none;border-radius:6px;">Sign in</a></p>'
            . '<p><a href="' . mci_mail_h($dash) . '">Your dashboard</a></p>';
    }
    $html .= '<p style="font-size:13px;color:#666;">Questions? <a href="mailto:' . mci_mail_h($contact) . '">' . mci_mail_h($contact) . '</a></p>'
        . '<p style="font-size:13px;color:#666;">— My City Info</p></div>';

    mci_mail_try_send(static function (PHPMailer $mail) use ($toEmail, $plain, $html): void {
        $mail->addAddress($toEmail);
        $mail->Subject = 'Your My City Info account';
        $mail->Body    = $html;
        $mail->AltBody = $plain;
        $mail->isHTML(true);
    });
}

function mci_mail_send_password_reset(string $toEmail, string $rawToken): void
{
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL) || $rawToken === '') {
        return;
    }

    $base     = api_public_site_base_url();
    $contact  = mci_mail_contact_email();
    $resetUrl = $base . '/reset-password/?token=' . rawurlencode($rawToken);

    $plain = "Hi,\n\n"
        . "We received a request to reset the password for the My City Info account associated with {$toEmail}.\n\n"
        . "Reset your password (link expires in 1 hour):\n{$resetUrl}\n\n"
        . "If you didn’t ask for this, you can ignore this email. Your password will stay the same.\n\n"
        . "If you’re worried about your account, contact us at {$contact}.\n\n"
        . "— My City Info\n";

    $html = '<div style="max-width:560px;font-family:system-ui,Segoe UI,sans-serif;font-size:15px;line-height:1.5;color:#222;">'
        . '<h1 style="font-size:20px;margin:0 0 12px;">Reset your password</h1>'
        . '<p>We received a request for <strong>' . mci_mail_h($toEmail) . '</strong>.</p>'
        . '<p style="margin:20px 0;"><a href="' . mci_mail_h($resetUrl) . '" style="display:inline-block;padding:10px 18px;background:#111;color:#fff;text-decoration:none;border-radius:6px;">Choose a new password</a></p>'
        . '<p style="font-size:13px;color:#666;">This link expires in <strong>1 hour</strong>.</p>'
        . '<p style="font-size:13px;color:#666;">If you didn’t ask for this, you can ignore this email.</p>'
        . '<p style="font-size:13px;color:#666;">If you’re worried about your account, contact <a href="mailto:' . mci_mail_h($contact) . '">' . mci_mail_h($contact) . '</a>.</p>'
        . '<p style="font-size:13px;color:#666;">— My City Info</p></div>';

    mci_mail_try_send(static function (PHPMailer $mail) use ($toEmail, $plain, $html): void {
        $mail->addAddress($toEmail);
        $mail->Subject = 'Reset your My City Info password';
        $mail->Body    = $html;
        $mail->AltBody = $plain;
        $mail->isHTML(true);
    });
}

/**
 * @param 'self'|'reset_token'|'admin' $context
 */
function mci_mail_send_password_changed(string $toEmail, string $context): void
{
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $base    = api_public_site_base_url();
    $contact = mci_mail_contact_email();
    $forgot  = $base . '/forgot-password/';
    $sub     = $base . '/login/';
    $cp      = $base . '/cp/login/';
    $when    = gmdate('Y-m-d H:i:s') . ' UTC';

    $adminLinePlain = '';
    $adminLineHtml  = '';
    if ($context === 'admin') {
        $adminLinePlain = "This update was made by a My City Info administrator on your account.\n\n";
        $adminLineHtml  = '<p><strong>This update was made by a My City Info administrator on your account.</strong></p>';
    }

    $plain = "Hi,\n\n"
        . $adminLinePlain
        . "Your password for My City Info ({$toEmail}) was changed on {$when}.\n\n"
        . "If you made this change, no further action is needed.\n\n"
        . "If you did not make this change, reset your password immediately:\n{$forgot}\n\n"
        . "Or contact us: {$contact}\n\n"
        . "Subscriber sign-in: {$sub}\n"
        . "Control panel sign-in: {$cp}\n\n"
        . "— My City Info\n";

    $html = '<div style="max-width:560px;font-family:system-ui,Segoe UI,sans-serif;font-size:15px;line-height:1.5;color:#222;">'
        . '<h1 style="font-size:20px;margin:0 0 12px;">Password updated</h1>'
        . $adminLineHtml
        . '<p>Your password for My City Info (<strong>' . mci_mail_h($toEmail) . '</strong>) was changed on '
        . '<strong>' . mci_mail_h($when) . '</strong>.</p>'
        . '<p>If you made this change, no further action is needed.</p>'
        . '<p style="margin:20px 0;"><a href="' . mci_mail_h($forgot) . '" style="display:inline-block;padding:10px 18px;background:#111;color:#fff;text-decoration:none;border-radius:6px;">Forgot password / get a new link</a></p>'
        . '<p>Or contact <a href="mailto:' . mci_mail_h($contact) . '">' . mci_mail_h($contact) . '</a></p>'
        . '<p><a href="' . mci_mail_h($sub) . '">Subscriber sign-in</a> · <a href="' . mci_mail_h($cp) . '">Control panel sign-in</a></p>'
        . '<p style="font-size:13px;color:#666;">— My City Info</p></div>';

    mci_mail_try_send(static function (PHPMailer $mail) use ($toEmail, $plain, $html): void {
        $mail->addAddress($toEmail);
        $mail->Subject = 'Your My City Info password was updated';
        $mail->Body    = $html;
        $mail->AltBody = $plain;
        $mail->isHTML(true);
    });
}
