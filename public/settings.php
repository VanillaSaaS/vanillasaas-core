<?php
/**
 * =============================================================================
 *  public/settings.php — ACCOUNT SETTINGS
 * =============================================================================
 *  One page, three forms, told apart by the hidden `action` field:
 *    profile  → change name / email (email change needs current password)
 *    password → change password, sign out other devices
 *    delete   → permanently delete the account
 *
 *  WHY ASK FOR THE CURRENT PASSWORD AGAIN?
 *  If someone walks up to an unlocked laptop, or steals a session cookie,
 *  they still can't change the email (and then reset the password via that
 *  email), change the password, or delete the account. That turns a
 *  temporary compromise into a permanent one — so we block it.
 * =============================================================================
 */

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

allow_methods('GET', 'POST');
$user = require_auth();

/**
 * Check the signed-in user's current password, with its own rate limit so a
 * stolen session can't be used to brute-force the password from this page.
 * Returns an error message, or null if the password is correct.
 */
function confirm_current_password(array $user, string $password): ?string
{
    $key = throttle_key('confirm-password', (string) $user['id']);

    if (throttle_too_many($key, 5)) {
        return 'Too many incorrect attempts. Try again in ' . throttle_wait_text(throttle_seconds_left($key)) . '.';
    }
    if ($password === '' || !password_verify($password, $user['password_hash'])) {
        throttle_hit($key, 900);
        return 'Your current password is not correct.';
    }
    throttle_clear($key);
    return null;
}

$errors = [];
$old    = [];
$status = 200;

if (is_post()) {
    csrf_verify();

    switch (input('action')) {

        // ---------------------------------------------------------------------
        case 'profile':
            $name  = input('name');
            $email = normalize_email(input('email'));
            $old   = ['name' => $name, 'email' => $email];

            $e = array_filter([
                'name'  => validate_name($name),
                'email' => validate_email($email),
            ]);

            $emailChanged = $email !== $user['email'];
            if (!$e && $emailChanged) {
                if ($error = confirm_current_password($user, input('profile_password', trim: false))) {
                    $e['profile_password'] = $error;
                }
            }

            if (!$e) {
                try {
                    db_run('UPDATE users SET name = ?, email = ?, updated_at = ? WHERE id = ?',
                        [$name, $email, now_utc(), $user['id']]);

                    // Any pending reset link was sent to the OLD address. Kill it.
                    if ($emailChanged) {
                        db_run('DELETE FROM password_resets WHERE user_id = ?', [$user['id']]);
                    }
                    flash('success', 'Profile saved.');
                    redirect('settings.php');
                } catch (PDOException $ex) {
                    if ($ex->getCode() !== '23000') {
                        throw $ex;
                    }
                    $e['email'] = 'That email is already used by another account.';
                }
            }
            $errors['profile'] = $e;
            break;

        // ---------------------------------------------------------------------
        case 'password':
            $current = input('current_password', trim: false);
            $new     = input('new_password', trim: false);
            $confirm = input('new_password_confirmation', trim: false);

            $e = [];
            if ($error = confirm_current_password($user, $current)) {
                $e['current_password'] = $error;
            } else {
                $e = array_filter([
                    'new_password' => validate_password($new, $user['email'])
                        ?? ($new === $current ? 'Choose a password different from your current one.' : null),
                    'new_password_confirmation' => $new !== $confirm ? 'Passwords do not match.' : null,
                ]);
            }

            if (!$e) {
                db_run('UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?',
                    [password_make($new), now_utc(), $user['id']]);
                db_run('DELETE FROM password_resets WHERE user_id = ?', [$user['id']]);

                // Everyone else using this account is now signed out; this
                // browser stays signed in with a fresh session ID.
                auth_logout_other_sessions((int) $user['id']);

                flash('success', 'Password updated. Any other devices have been signed out.');
                redirect('settings.php');
            }
            $errors['password'] = $e;
            break;

        // ---------------------------------------------------------------------
        case 'delete':
            if ($error = confirm_current_password($user, input('delete_password', trim: false))) {
                $errors['delete'] = ['delete_password' => $error];
                break;
            }

            // ON DELETE CASCADE in the schema removes the user's reset tokens.
            // When you add your own tables (projects, invoices...), give their
            // user_id foreign keys ON DELETE CASCADE too, or delete them here.
            db_run('DELETE FROM users WHERE id = ?', [$user['id']]);

            auth_logout();
            flash('success', 'Your account has been deleted. Sorry to see you go.');
            redirect('index.php');

        default:
            abort(400);
    }

    $status = 422;
}

view('pages/settings', [
    'title'     => 'Settings',
    'active'    => 'settings',
    'user'      => $user,
    'errors'    => $errors,
    'old'       => $old,
    'minLength' => (int) config('auth.password_min', 12),
], 'app', $status);
