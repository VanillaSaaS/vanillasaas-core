<?php
/**
 * =============================================================================
 *  app/lib/throttle.php — RATE LIMITING
 * =============================================================================
 *
 *  CORE FILE — replaced by VanillaSaaS Core updates. Don't edit it; put your
 *  own functions in app/custom.php so updates stay a simple file swap.
 *
 *  WHY
 *  ---
 *  Without limits, a bot can try 10,000 passwords a minute against one
 *  account, or create 10,000 junk accounts. A counter per "who + what",
 *  stored in the database, stops both.
 *
 *  HOW (fixed window)
 *  ------------------
 *  Each key has a counter and an expiry time. Every attempt adds 1. When the
 *  counter reaches the limit, further attempts are refused until the expiry
 *  passes, then the counter starts again from zero.
 *
 *      $key = throttle_key('login', $email, client_ip());
 *      if (throttle_too_many($key, 5)) { ...refuse... }
 *      throttle_hit($key, 900);   // count this attempt for 15 minutes
 *
 *  Stored in the database (not the session) because an attacker simply
 *  throws their session cookie away between attempts.
 *
 *  SCALE NOTE: perfect for a single server. At very high traffic you'd move
 *  this to Redis/Memcached; the four functions below are the only things
 *  you'd need to rewrite.
 * =============================================================================
 */

declare(strict_types=1);

/** Build a key from parts. Hashed, so no raw emails/IPs are stored. */
function throttle_key(string ...$parts): string
{
    return hash('sha256', strtolower(implode('|', $parts)));
}

/** Has this key reached $max attempts in its current window? */
function throttle_too_many(string $key, int $max): bool
{
    $row = db_one('SELECT hits, reset_at FROM throttle WHERE throttle_key = ?', [$key]);
    return $row !== null && (int) $row['reset_at'] > time() && (int) $row['hits'] >= $max;
}

/** Record one attempt. Starts a new window of $decaySeconds if none is active. */
function throttle_hit(string $key, int $decaySeconds): void
{
    $now = time();

    // Try to bump a live counter first (the common case: one query).
    $updated = db_run(
        'UPDATE throttle SET hits = hits + 1 WHERE throttle_key = ? AND reset_at > ?',
        [$key, $now]
    );

    if ($updated === 0) {
        // No live counter: clear any expired one and start fresh.
        db_run('DELETE FROM throttle WHERE throttle_key = ?', [$key]);
        try {
            db_run('INSERT INTO throttle (throttle_key, hits, reset_at) VALUES (?, 1, ?)', [$key, $now + $decaySeconds]);
        } catch (PDOException) {
            // Two requests raced and the other inserted first. Just count ours.
            db_run('UPDATE throttle SET hits = hits + 1 WHERE throttle_key = ?', [$key]);
        }
    }

    // Housekeeping: about 1 request in 50 deletes expired rows so the table
    // can't grow forever. No cron job needed.
    if (random_int(1, 50) === 1) {
        db_run('DELETE FROM throttle WHERE reset_at <= ?', [$now]);
    }
}

/** Forget a key, e.g. reset the failed-login count after a successful login. */
function throttle_clear(string $key): void
{
    db_run('DELETE FROM throttle WHERE throttle_key = ?', [$key]);
}

/** Seconds until this key's window resets (0 if no active window). */
function throttle_seconds_left(string $key): int
{
    $row = db_one('SELECT reset_at FROM throttle WHERE throttle_key = ?', [$key]);
    return $row ? max(0, (int) $row['reset_at'] - time()) : 0;
}

/** Human-friendly wait time for error messages: "about 12 minutes". */
function throttle_wait_text(int $seconds): string
{
    if ($seconds < 60) {
        return 'a minute';
    }
    $minutes = (int) ceil($seconds / 60);
    return $minutes === 1 ? 'a minute' : "about {$minutes} minutes";
}
