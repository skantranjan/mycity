<?php

declare(strict_types=1);

/**
 * Application-wide constants.
 *
 * Import this file wherever you need these values instead of repeating
 * magic numbers inline.
 */

// Public listing pages (business-listing, business-category, products, services)
const MCI_LISTING_PER_PAGE       = 12;
const MCI_ITEMS_PER_PAGE         = 12;

// Control panel
const MCI_CP_LISTING_PER_PAGE    = 25;
const MCI_CP_ERRORLOG_PER_PAGE   = 50;
const MCI_CP_DASHBOARD_PENDING_LIMIT = 10;

// Subscriber dashboard
const MCI_DASHBOARD_RECENT_LIMIT = 5;

// Branding
const MCI_FOUNDING_YEAR = 2020;

// Google AdSense (ca-pub-…). Empty string disables the sitewide loader in layout.
const MCI_ADSENSE_CLIENT_ID = 'ca-pub-8738802980664354';

// ---------------------------------------------------------------------------
// Error logging
// ---------------------------------------------------------------------------

/**
 * Write a structured entry to logs/api-errors.log.
 * Call this wherever you catch a Throwable you want to diagnose.
 */
function mci_log_error(string $context, Throwable $e): void
{
    $logFile = dirname(__DIR__) . '/logs/api-errors.log';
    $line = implode(' | ', [
        date('Y-m-d H:i:s'),
        $context,
        get_class($e) . ': ' . $e->getMessage(),
        $e->getFile() . ':' . $e->getLine(),
        // compact one-line trace (first 5 frames)
        implode(' -> ', array_slice(
            array_map(
                static fn(array $f): string =>
                    ($f['file'] ?? '?') . ':' . ($f['line'] ?? '?') . ' ' . ($f['function'] ?? ''),
                $e->getTrace()
            ),
            0, 5
        )),
    ]);
    error_log($line . PHP_EOL, 3, $logFile);
}
