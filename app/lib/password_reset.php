<?php
/**
 * =============================================================================
 *  app/lib/password_reset.php — "FORGOT PASSWORD" TOKENS
 * =============================================================================
 *
 *  CORE FILE — replaced by VanillaSaaS Core updates. Don't edit it; put your
 *  own functions in app/custom.php so updates stay a simple file swap.
 *
 *  FLOW
 *  ----
 *  1. User enters their email on forgot-password.php.
 *  2. If an account exists, we create a random token, store its SHA-256
 *     HASH with an expiry, and email the plain token inside a link.
 *  3. The user clicks the link → reset-password.php hashes the token from the
 *     URL and looks the hash up. Found and not expired → show the form.
 *  4. On success we change the password, delete ALL of that user's tokens
 *     (single use), and sign out every existing session.
 *
 *  WHY HASH THE TOKEN?
 *  A reset token is as powerful as a password. If the database leaks
 *  (backup left on a server, SQL injection elsewhere), stored hashes are
 *  useless to the attacker. SHA-256 (not password_hash) is fine here because
 *  the token is 256 random bits — there's nothing to brute-force.
 *
 *  WHY ALWAYS SAY "IF AN ACCOUNT EXISTS, WE'VE SENT A LINK"?
 *  So the form can't be used to discover which emails are registered.
 * =============================================================================
 */

declare(strict_types=1);

/** Create a token and email the link — silently does nothing for unknown emails. */
function reset_request(string $email): void
{
    $user = user_find_by_email($email);
    if ($user === null) {
        // Spend roughly the time a real request would, so response timing
        // doesn't reveal whether the account exists.
        usleep(random_int(150_000, 350_000));
        return;
    }

    $token   = bin2hex(random_bytes(32));                // 64 hex chars
    $minutes = (int) config('auth.reset_expires_minutes', 60);
    $expires = gmdate('Y-m-d H:i:s', time() + $minutes * 60);

    db_transaction(function () use ($user, $token, $expires): void {
        // Only the newest link works: older unused links are invalidated.
        db_run('DELETE FROM password_resets WHERE user_id = ?', [$user['id']]);
        db_run(
            'INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?)',
            [$user['id'], hash('sha256', $token), $expires, now_utc()]
        );
    });

    $app  = (string) config('app.name');
    $link = base_url() . '/reset-password.php?token=' . $token;

    $body = <<<TXT
    Hi {$user['name']},

    Someone (hopefully you) asked to reset the password for your {$app} account.

    Choose a new password here:
    {$link}

    This link works once and expires in {$minutes} minutes.

    If you didn't ask for this, ignore this email — your password won't change.

    — {$app}
    TXT;

    mail_send($user['email'], "Reset your {$app} password", $body);
}

/**
 * Look up a token from a reset link. Returns the matching row (with the
 * user's id and email) if it exists and hasn't expired, otherwise null.
 */
function reset_find_valid(string $token): ?array
{
    // Reject anything that isn't the exact shape we issue before touching the DB.
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }

    return db_one(
        'SELECT pr.id, pr.user_id, u.email
           FROM password_resets pr
           JOIN users u ON u.id = pr.user_id
          WHERE pr.token_hash = ? AND pr.expires_at > ?',
        [hash('sha256', $token), now_utc()]
    );
}

/** Set the new password, burn all tokens, and sign out every session. */
function reset_complete(array $reset, string $newPassword): void
{
    db_transaction(function () use ($reset, $newPassword): void {
        db_run(
            'UPDATE users
                SET password_hash = ?, session_version = session_version + 1, updated_at = ?
              WHERE id = ?',
            [password_make($newPassword), now_utc(), $reset['user_id']]
        );
        db_run('DELETE FROM password_resets WHERE user_id = ?', [$reset['user_id']]);
    });
}
