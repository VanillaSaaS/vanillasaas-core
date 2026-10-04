<?php
/**
 * =============================================================================
 *  app/lib/session.php — A HARDENED PHP SESSION
 * =============================================================================
 *
 *  CORE FILE — replaced by VanillaSaaS Core updates. Don't edit it; put your
 *  own functions in app/custom.php so updates stay a simple file swap.
 *
 *  HOW SESSIONS WORK (30-second version)
 *  -------------------------------------
 *  The browser holds a random ID in a cookie. The server holds the data
 *  ($_SESSION) in a file named after that ID. Whoever has the ID *is* the
 *  user, so everything below is about keeping that ID secret and short-lived.
 *
 *  WHAT WE HARDEN
 *  --------------
 *  HttpOnly        JavaScript can't read the cookie, so an XSS bug can't
 *                  steal the session.
 *  Secure          over HTTPS, the cookie is never sent over plain HTTP.
 *  SameSite=Lax    the cookie isn't sent on cross-site POSTs (a CSRF layer
 *                  on top of our tokens). We use Lax, not Strict: Strict would
 *                  make users appear logged-out when they click a link to
 *                  your app from an email or another site.
 *  strict_mode     PHP rejects session IDs it didn't create itself, which
 *                  blocks "session fixation" (attacker plants a known ID).
 *  Own save path   session files live in storage/sessions, not the shared
 *                  /tmp that other sites on a shared host can read.
 *  Timeouts        idle + absolute lifetime, enforced on the server.
 * =============================================================================
 */

declare(strict_types=1);

function session_start_secure(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = http_is_https();
    $idle   = (int) config('session.idle_timeout', 7200);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');      // never put the ID in URLs
    ini_set('session.cookie_httponly', '1');
    ini_set('session.save_path', STORAGE_PATH . '/sessions');

    // Garbage collection: delete session files untouched for longer than the
    // idle timeout. Roughly 1 in 100 requests does the sweep.
    ini_set('session.gc_maxlifetime', (string) ($idle + 300));
    ini_set('session.gc_probability', '1');
    ini_set('session.gc_divisor', '100');

    // The "__Host-" prefix is a browser-enforced promise: the cookie must be
    // Secure, Path=/, and have no Domain. Only valid over HTTPS.
    $name = (string) config('session.name', 'app_session');
    session_name($secure ? '__Host-' . $name : $name);

    session_set_cookie_params([
        'lifetime' => 0,          // cookie dies when the browser closes
        'path'     => '/',
        'domain'   => '',         // this exact host only, no subdomains
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
    session_enforce_timeouts();
}

/**
 * Sign the user out if they've been idle too long, or signed in too long.
 * Enforced here on the server — never trust a cookie's own expiry date,
 * because the client controls it.
 */
function session_enforce_timeouts(): void
{
    $now = time();

    if (isset($_SESSION['user_id'])) {
        $idleFor  = $now - (int) ($_SESSION['last_activity'] ?? 0);
        $loggedIn = $now - (int) ($_SESSION['login_at'] ?? 0);

        if ($idleFor > (int) config('session.idle_timeout')
            || $loggedIn > (int) config('session.absolute_timeout')) {
            session_reset_secure();
            flash('info', 'Your session expired. Please sign in again.');
        }
    }

    $_SESSION['last_activity'] = $now;
}

/**
 * Wipe the session and move to a brand-new session ID.
 *
 * session_regenerate_id(true) deletes the old session file, so the old ID —
 * even if someone copied it — now points at nothing. We keep a (new, empty)
 * session alive so we can still show a "you've been signed out" message.
 */
function session_reset_secure(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['last_activity'] = time();
}

// -----------------------------------------------------------------------------
// Flash messages: shown once on the next page, then gone.
// -----------------------------------------------------------------------------

/**
 * Queue a one-time message for the next page view.
 *   flash('success', 'Password updated.');
 * Types used by the CSS: success, error, info.
 */
function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

/** Return queued messages and clear them, so they display exactly once. */
function flash_pull(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $messages;
}
