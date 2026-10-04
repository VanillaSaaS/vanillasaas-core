<?php
/**
 * =============================================================================
 *  public/reset-password.php — SET A NEW PASSWORD FROM AN EMAILED LINK
 * =============================================================================
 *  GET  ?token=...  → show the form if the token is valid
 *  POST             → re-check the token, save the password, burn the token,
 *                     sign out all sessions, send the user to sign in
 *
 *  Not guarded with require_guest(): someone signed in on this browser may
 *  still legitimately use a reset link.
 * =============================================================================
 */

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

allow_methods('GET', 'POST');

// This page's URL contains a secret token. "no-referrer" guarantees the
// browser never sends that URL to another site if the user clicks a link.
header('Referrer-Policy: no-referrer');
http_no_store();

$token  = is_post() ? input('token') : query('token');
$reset  = reset_find_valid($token);
$errors = [];
$status = $reset ? 200 : 410;   // 410 Gone: the link existed but is no longer usable

if (is_post() && $reset) {
    csrf_verify();

    $password     = input('password', trim: false);
    $confirmation = input('password_confirmation', trim: false);

    $errors = array_filter([
        'password' => validate_password($password, $reset['email']),
        'password_confirmation' => $password !== $confirmation ? 'Passwords do not match.' : null,
    ]);

    if (!$errors) {
        reset_complete($reset, $password);

        // If this browser was signed in, end that session cleanly too.
        auth_logout();
        flash('success', 'Your password has been changed. Sign in with your new password.');
        redirect('login.php');
    }
    $status = 422;
}

view('pages/reset-password', [
    'title'     => 'Choose a new password',
    'valid'     => $reset !== null,
    'token'     => $token,
    'email'     => $reset['email'] ?? '',
    'errors'    => $errors,
    'minLength' => (int) config('auth.password_min', 12),
], 'auth', $status);
