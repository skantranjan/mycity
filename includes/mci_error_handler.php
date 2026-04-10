<?php
declare(strict_types=1);

/**
 * Global error handling:
 * - Converts PHP errors into exceptions
 * - Catches exceptions + fatal shutdowns
 * - Logs details server-side and shows a friendly message
 *
 * Note: cannot catch parse errors (syntax errors) happening before PHP runs.
 */

if (!isset($GLOBALS['mci_error_handler_installed'])) {
    $GLOBALS['mci_error_handler_installed'] = true;
    $GLOBALS['mci_error_handling_in_progress'] = false;

    $logContext = static function (): array {
        $uri = '';
        try {
            $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        } catch (Throwable $e) {
            $uri = '';
        }

        $userId = '';
        try {
            // Prefer CP user id, fall back to subscriber user id.
            $userId = (string) ($_SESSION['mci_cp_user_id'] ?? $_SESSION['mci_user_id'] ?? '');
        } catch (Throwable $e) {
            $userId = '';
        }

        $ip = '';
        try {
            $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        } catch (Throwable $e) {
            $ip = '';
        }

        return [
            'uri'    => $uri,
            'userId' => $userId,
            'ip'     => $ip,
        ];
    };

    /**
     * Write one row to mci_error_log. Silently falls back to error_log() on failure.
     */
    $writeToDb = static function (
        string $level,
        string $message,
        string $file,
        int    $line,
        string $uri,
        string $userId,
        string $ip,
        ?array $extra = null
    ): void {
        try {
            // Only attempt DB write if our db helper is already loaded (avoids recursive bootstrap issues).
            if (!function_exists('api_db')) {
                return;
            }
            $pdo = api_db();
            $stmt = $pdo->prepare(
                'INSERT INTO mci_error_log (level, message, file, line, uri, user_id, ip, context)
                 VALUES (:level, :message, :file, :line, :uri, :user_id, :ip, :context)'
            );
            $stmt->execute([
                ':level'   => $level,
                ':message' => $message,
                ':file'    => $file ?: null,
                ':line'    => $line ?: null,
                ':uri'     => $uri ?: null,
                ':user_id' => $userId !== '' ? $userId : null,
                ':ip'      => $ip ?: null,
                ':context' => $extra !== null ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null,
            ]);
        } catch (Throwable $ignored) {
            // DB unavailable — already logged via error_log() by caller; swallow silently.
        }
    };

    $renderFriendly = static function (string $title, string $message, ?int $statusCode = null): void {
        if ($GLOBALS['mci_error_handling_in_progress'] === true) {
            // Last resort: keep it extremely small.
            echo 'Something went wrong.';
            return;
        }
        $GLOBALS['mci_error_handling_in_progress'] = true;

        $statusCode = $statusCode ?? 500;
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: text/html; charset=utf-8');
        }

        // Best-effort: clear any partial output.
        try {
            if (ob_get_length() !== false && ob_get_length() > 0) {
                @ob_end_clean();
            }
        } catch (Throwable $e) {
            // ignore
        }

        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        echo <<<HTML
<!doctype html>
<html lang="en">
  <head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-9ZLMGV0Q96"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-9ZLMGV0Q96');
    </script>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{$safeTitle}</title>
    <link rel="stylesheet" href="/assets/css/theme.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  </head>
  <body style="background:#fff;color:#0f172a;">
    <div class="container py-5">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
          <h1 class="h4 mb-2">{$safeTitle}</h1>
          <p class="text-muted">{$safeMessage}</p>
          <div class="d-flex gap-2 mt-3 flex-wrap">
            <a class="btn btn-sm btn-dark" href="/"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Go home</a>
            <a class="btn btn-sm btn-outline-dark" href="javascript:location.reload()">Try again</a>
          </div>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
HTML;
    };

    $logThrowable = static function (Throwable $e) use ($writeToDb): void {
        $ctx  = $logContext();
        $line = (int) ($e->getLine() ?? 0);
        $file = (string) ($e->getFile() ?? '');
        $msg  = (string) $e->getMessage();
        $class = get_class($e);

        // Always write to PHP error log as fallback.
        $detail = $class . ': ' . $msg . ' | file=' . $file . ' | line=' . $line . ' | uri=' . $ctx['uri'] . ' | userId=' . $ctx['userId'];
        error_log('[MyCityInfo] ' . $detail);

        // Write structured row to DB.
        $writeToDb(
            'exception',
            $class . ': ' . $msg,
            $file,
            $line,
            $ctx['uri'],
            $ctx['userId'],
            $ctx['ip'],
            ['trace' => mb_substr($e->getTraceAsString(), 0, 2048)]
        );
    };

    set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
        // Ignore suppressed errors.
        if (0 === error_reporting()) {
            return false;
        }
        // Convert to exception so exception handler can render once.
        throw new ErrorException($message, 0, $severity, $file, $line);
    });

    set_exception_handler(static function (Throwable $e) use ($logThrowable, $renderFriendly): void {
        try {
            $logThrowable($e);
        } catch (Throwable $ignored) {
            // ignore logging failures
        }
        $renderFriendly('Unexpected error', 'Sorry—something went wrong. Please try again in a moment.', 500);
        exit;
    });

    register_shutdown_function(static function () use ($writeToDb, $renderFriendly): void {
        $err = error_get_last();
        if (!$err) {
            return;
        }

        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array((int) ($err['type'] ?? 0), $fatalTypes, true)) {
            return;
        }

        // Log the fatal error.
        try {
            $ctx  = $logContext();
            $msg  = (string) ($err['message'] ?? '');
            $file = (string) ($err['file'] ?? '');
            $line = (int) ($err['line'] ?? 0);
            $detail = 'Fatal: ' . $msg . ' | file=' . $file . ' | line=' . $line . ' | uri=' . $ctx['uri'] . ' | userId=' . $ctx['userId'];
            error_log('[MyCityInfo] ' . $detail);

            $writeToDb('fatal', 'Fatal: ' . $msg, $file, $line, $ctx['uri'], $ctx['userId'], $ctx['ip']);
        } catch (Throwable $ignored) {
            // ignore
        }

        $renderFriendly('Service temporarily unavailable', 'A server error occurred. Please try again later.', 503);
    });
}

