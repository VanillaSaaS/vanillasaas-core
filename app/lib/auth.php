<?php
/**
 * =============================================================================
 *  app/lib/auth.php — ACCOUNTS: REGISTER, SIGN IN, SIGN OUT, GUARD PAGES
 * =============================================================================
 *
 *  CORE FILE — replaced by VanillaSaaS Core updates. Don't edit it; put your
 *  own functions in app/custom.php so updates stay a simple file swap.
 *
 *  Page guards (put one at the top of a page):
 *      require_auth();   // signed-in users only (dashboard, settings...)
 *      require_guest();  // signed-out users only (login, register...)
 *
 *  Current user, anywhere:
 *      $user = auth_user();   // array with id, name, email... or null
 *
 *  PASSWORD STORAGE
 *  ----------------
 *  We never store passwords — only a one-way hash from password_hash().
 *  We prefer Argon2id (winner of the Password Hashing Competition, resistant
 *  to GPU cracking). Some shared hosts compile PHP without it; there we fall
 *  back to bcrypt, which is still strong. If you later move to a host with
 *  Argon2id, users are upgraded silently the next time they sign in
 *  (see password_needs_rehash in auth_attempt()).
 * =============================================================================
 */

declare(strict_types=1);

// -----------------------------------------------------------------------------
// Passwords
// -----------------------------------------------------------------------------

/** The strongest algorithm this server supports. */
function password_algo(): string|int|null
{
    return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
}

function password_make(string $password): string
{
    return password_hash($password, password_algo());
}

/**
 * A throwaway hash we verify against when the email ISN'T registered.
 *
 * Why: checking a real password takes ~50–100ms (deliberately slow).
 * If unknown emails returned instantly, an attacker could time the login
 * form to discover which emails have accounts. Verifying against a dummy
 * hash makes both paths take the same time.
 *
 * Generated once with this server's real algorithm and cost, then cached.
 */
function password_dummy_hash(): string
{
    $file = STORAGE_PATH . '/cache/dummy-password-hash.txt';
    $hash = is_file($file) ? trim((string) file_get_contents($file)) : '';

    if ($hash === '' || password_needs_rehash($hash, password_algo())) {
        $hash = password_make(bin2hex(random_bytes(16)));
        @file_put_contents($file, $hash, LOCK_EX);
    }
    return $hash;
}

// -----------------------------------------------------------------------------
// Users
// -----------------------------------------------------------------------------

function user_find_by_email(string $email): ?array
{
    return db_one('SELECT * FROM users WHERE email = ?', [normalize_email($email)]);
}

function user_find_by_id(int $id): ?array
{
    return db_one('SELECT * FROM users WHERE id = ?', [$id]);
}

/**
 * Create an account. Returns the new user's id, or null if the email is
 * already taken. Inputs must already be validated.
 */
function auth_register(string $name, string $email, string $password): ?int
{
    $now = now_utc();
    try {
        db_run(
            'INSERT INTO users (name, email, password_hash, session_version, created_at, updated_at)
             VALUES (?, ?, ?, 1, ?, ?)',
            [$name, normalize_email($email), password_make($password), $now, $now]
        );
    } catch (PDOException $ex) {
        // SQLSTATE 23000 = integrity constraint violation, here the UNIQUE
        // email index. We rely on the database (not a SELECT beforehand) as
        // the source of truth, which is also safe if two signups race.
        if ($ex->getCode() === '23000') {
            return null;
        }
        throw $ex;
    }
    return (int) db()->lastInsertId();
}

/**
 * Check an email + password. Returns the user on success, null on failure.
 * Deliberately does NOT say which part was wrong.
 */
function auth_attempt(string $email, string $password): ?array
{
    $user = user_find_by_email($email);

    if ($user === null) {
        password_verify($password, password_dummy_hash()); // equalise timing
        return null;
    }

    if (!password_verify($password, $user['password_hash'])) {
        return null;
    }

    // Upgrade old hashes (e.g. bcrypt → Argon2id, or a higher cost factor)
    // while we briefly have the plain password in memory.
    if (password_needs_rehash($user['password_hash'], password_algo())) {
        db_run('UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?',
            [password_make($password), now_utc(), $user['id']]);
    }

    return $user;
}

/**
 * Start a signed-in session for $user.
 *
 * session_regenerate_id(true) is the critical line: it issues a NEW session
 * ID at the moment of login. If an attacker had somehow planted or seen the
 * pre-login ID, it becomes worthless. (Defeats "session fixation".)
 */
function auth_login(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user_id']         = (int) $user['id'];
    $_SESSION['session_version'] = (int) $user['session_version'];
    $_SESSION['login_at']        = time();
    $_SESSION['last_activity']   = time();
    // Remember the PREVIOUS sign-in time for the dashboard before we overwrite it.
    $_SESSION['previous_login_at'] = $user['last_login_at'] ?? null;
    csrf_rotate();

    db_run('UPDATE users SET last_login_at = ? WHERE id = ?', [now_utc(), $user['id']]);
    auth_user(refresh: true);
}

/** End the session completely. */
function auth_logout(): void
{
    session_reset_secure();
    auth_user(refresh: true);
}

/**
 * The signed-in user (fresh from the database), or null.
 *
 * Loaded once per request and cached. We also compare session_version: if
 * the user changed their password on another device, the number in the
 * database has moved on and this older session is signed out.
 */
function auth_user(bool $refresh = false): ?array
{
    static $user = null;
    static $loaded = false;

    if ($refresh) {
        $loaded = false;
        $user = null;
    }
    if ($loaded) {
        return $user;
    }
    $loaded = true;

    $id = (int) ($_SESSION['user_id'] ?? 0);
    if ($id === 0) {
        return null;
    }

    $found = user_find_by_id($id);
    if ($found === null || (int) $found['session_version'] !== (int) ($_SESSION['session_version'] ?? -1)) {
        // Account deleted, or signed out everywhere. Drop this session.
        session_reset_secure();
        flash('info', 'You have been signed out. Please sign in again.');
        return null;
    }

    return $user = $found;
}

/** Shortcut: the signed-in user's id, or null. */
function auth_id(): ?int
{
    $user = auth_user();
    return $user ? (int) $user['id'] : null;
}

/**
 * Sign out every OTHER session for this user (e.g. after a password change)
 * while keeping the current browser signed in.
 */
function auth_logout_other_sessions(int $userId): void
{
    db_run('UPDATE users SET session_version = session_version + 1, updated_at = ? WHERE id = ?', [now_utc(), $userId]);

    if ((int) ($_SESSION['user_id'] ?? 0) === $userId) {
        $user = user_find_by_id($userId);
        session_regenerate_id(true);
        $_SESSION['session_version'] = (int) $user['session_version'];
        auth_user(refresh: true);
    }
}

// -----------------------------------------------------------------------------
// Page guards
// -----------------------------------------------------------------------------

/**
 * Only signed-in users may view this page.
 *
 * Guests are sent to the login page; we remember where they were going so we
 * can bring them back afterwards (GET requests only, local paths only).
 */
function require_auth(): array
{
    $user = auth_user();

    if ($user === null) {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            $_SESSION['intended'] = safe_local_path($_SERVER['REQUEST_URI'] ?? null, '');
        }
        if (!isset($_SESSION['_flash'])) {
            flash('info', 'Please sign in to continue.');
        }
        redirect('login.php');
    }

    // Private pages must never be cached (shared computers, Back button).
    http_no_store();
    return $user;
}

/** Only signed-OUT visitors may view this page (login, register...). */
function require_guest(): void
{
    if (auth_user() !== null) {
        redirect('dashboard.php');
    }
}
