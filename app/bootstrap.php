<?php
/**
 * =============================================================================
 *  app/bootstrap.php — EVERY REQUEST STARTS HERE
 * =============================================================================
 *
 *  CORE FILE — replaced by VanillaSaaS Core updates. Don't edit it; put your
 *  own functions in app/custom.php so updates stay a simple file swap.
 *
 *  Each page in /public begins with:
 *
 *      require __DIR__ . '/../app/bootstrap.php';
 *
 *  That one line gives the page: configuration, error handling, security
 *  headers, a hardened session, the database, CSRF protection, auth helpers
 *  and the view renderer. Order matters, so each step is numbered.
 *
 *  ARCHITECTURE IN ONE PARAGRAPH
 *  -----------------------------
 *  This is the "page controller" pattern: one PHP file per URL in /public.
 *  Each file handles its own GET (show the page) and POST (process the form),
 *  then hands data to a template in app/views. No router, no framework, no
 *  build step. To add a page, copy dashboard.php, rename it, edit it.
 * =============================================================================
 */

declare(strict_types=1);

// -----------------------------------------------------------------------------
// 1. Refuse to run on unsupported PHP.
// -----------------------------------------------------------------------------
// We use PHP 8.1 features (e.g. the `never` return type, str_contains). Failing
// loudly here beats a cryptic syntax error three files deep.
if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    exit('VanillaSaaS Core requires PHP 8.1 or newer. This server runs PHP ' . PHP_VERSION . '.');
}

// -----------------------------------------------------------------------------
// 2. Paths. APP_ROOT is the project folder (the one containing /app, /public).
// -----------------------------------------------------------------------------
define('APP_ROOT', dirname(__DIR__));
define('STORAGE_PATH', APP_ROOT . '/storage');
define('VIEW_PATH', __DIR__ . '/views');

// Which release of VanillaSaaS Core this is (from the VERSION file). Quote it
// when asking for support, and compare it with CHANGELOG.md before updating.
define('CORE_VERSION', is_file(APP_ROOT . '/VERSION') ? trim((string) file_get_contents(APP_ROOT . '/VERSION')) : 'unknown');

// -----------------------------------------------------------------------------
// 3. Load the libraries. Plain functions, grouped by concern. Read them in
//    this order if you're learning the codebase.
// -----------------------------------------------------------------------------
require __DIR__ . '/lib/helpers.php';   // config(), e(), redirect(), view()...
require __DIR__ . '/lib/errors.php';    // error/exception handling, abort()
require __DIR__ . '/lib/http.php';      // HTTPS detection, client IP, headers
require __DIR__ . '/lib/db.php';        // PDO connection + auto-install
require __DIR__ . '/lib/session.php';   // hardened session + flash messages
require __DIR__ . '/lib/csrf.php';      // cross-site request forgery tokens
require __DIR__ . '/lib/throttle.php';  // rate limiting (brute-force defence)
require __DIR__ . '/lib/validate.php';  // input validation rules
require __DIR__ . '/lib/auth.php';      // register, login, logout, guards
require __DIR__ . '/lib/mail.php';      // outgoing email
require __DIR__ . '/lib/password_reset.php'; // forgot-password tokens

// YOUR code. Everything above is replaced by Core updates; this file never is.
require __DIR__ . '/custom.php';

// -----------------------------------------------------------------------------
// 4. Configuration: app/config.php, overlaid by app/config.local.php if present.
// -----------------------------------------------------------------------------
config_load(__DIR__ . '/config.php', __DIR__ . '/config.local.php');

date_default_timezone_set((string) config('app.timezone', 'UTC'));

// -----------------------------------------------------------------------------
// 5. Error handling. From here on, every warning becomes an exception, every
//    exception is logged, and visitors never see a stack trace in production.
// -----------------------------------------------------------------------------
errors_register();

// -----------------------------------------------------------------------------
// 6. Make sure the writable folders exist (first run, or after a fresh upload
//    where empty folders were skipped by the FTP client).
// -----------------------------------------------------------------------------
foreach (['database', 'logs', 'sessions', 'cache'] as $dir) {
    $path = STORAGE_PATH . '/' . $dir;
    if (!is_dir($path) && !mkdir($path, 0750, true) && !is_dir($path)) {
        throw new RuntimeException("Cannot create {$path}. Make /storage writable by the web server.");
    }
}

// -----------------------------------------------------------------------------
// 7. Web-only steps. Command-line scripts (bin/install.php) skip these.
// -----------------------------------------------------------------------------
if (PHP_SAPI !== 'cli') {
    http_send_security_headers();
    session_start_secure();

    // 8. Your hook. If app/custom.php defines app_boot(), it runs here on every
    //    web request, once the session is available and before the page does
    //    anything. Use it for things every page needs (e.g. a "remember me"
    //    cookie check) instead of editing Core's files.
    if (function_exists('app_boot')) {
        app_boot();
    }
}
