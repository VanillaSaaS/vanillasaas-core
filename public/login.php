<?php
/**
 * =============================================================================
 *  public/login.php — SIGN IN
 * =============================================================================
 *  GET  → show the form
 *  POST → check rate limits → verify credentials → start session → redirect
 *
 *  Every page controller in /public follows this same shape:
 *    1. bootstrap   2. guard   3. handle POST   4. render view
 * =============================================================================
 */

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

allow_methods('GET', 'POST');
require_guest();                      // already signed in? go to the dashboard

$errors = [];
$email  = '';
$status = 200;

if (is_post()) {
    csrf_verify();                    // forged cross-site request? stop here

    $email    = normalize_email(input('email'));
    $password = input('password', trim: false);   // spaces are valid in passwords

    // --- Brute-force protection ---------------------------------------------
    // Two counters:
    //   per email+IP → stops guessing one account's password
    //   per IP       → stops one machine spraying many accounts
    // Counting per email+IP (not per email alone) means an attacker can't lock
    // a real user out of their own account just by failing logins for them.
    $ip      = client_ip();
    $userKey = throttle_key('login', $email, $ip);
    $ipKey   = throttle_key('login-ip', $ip);
    $decay   = (int) config('auth.login_decay_seconds', 900);

    if (throttle_too_many($userKey, (int) config('auth.login_max_attempts', 5))
        || throttle_too_many($ipKey, (int) config('auth.login_ip_max', 30))) {

        $wait = max(throttle_seconds_left($userKey), throttle_seconds_left($ipKey));
        $errors['form'] = 'Too many sign-in attempts. Please try again in ' . throttle_wait_text($wait) . '.';
        $status = 429;

    } elseif ($email === '' || $password === '') {
        $errors['form'] = 'Enter your email address and password.';
        $status = 422;

    } else {
        $user = auth_attempt($email, $password);

        if ($user !== null) {
            throttle_clear($userKey);
            auth_login($user);

            // Back to the page they originally wanted, or the dashboard.
            $to = safe_local_path($_SESSION['intended'] ?? null, 'dashboard.php');
            unset($_SESSION['intended']);
            redirect($to);
        }

        throttle_hit($userKey, $decay);
        throttle_hit($ipKey, $decay);

        // One message for "no such user" and "wrong password" alike, so the
        // form can't be used to discover which emails are registered.
        $errors['form'] = 'That email and password combination is not correct.';
        $status = 422;
    }
}

view('pages/login', [
    'title'  => 'Sign in',
    'errors' => $errors,
    'email'  => $email,
], 'auth', $status);
