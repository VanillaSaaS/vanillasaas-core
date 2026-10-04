<?php
/**
 * =============================================================================
 *  public/register.php — CREATE AN ACCOUNT
 * =============================================================================
 *  GET  → show the form
 *  POST → rate-limit → validate → insert user → sign in → dashboard
 * =============================================================================
 */

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

allow_methods('GET', 'POST');
require_guest();

$errors = [];
$old    = ['name' => '', 'email' => ''];
$status = 200;

if (is_post()) {
    csrf_verify();

    $name         = input('name');
    $email        = normalize_email(input('email'));
    $password     = input('password', trim: false);
    $confirmation = input('password_confirmation', trim: false);
    $old          = ['name' => $name, 'email' => $email];

    // Max 10 signup attempts per IP per hour: stops scripted account spam
    // and slows down anyone probing which emails are registered.
    $key = throttle_key('register', client_ip());

    if (throttle_too_many($key, 10)) {
        $errors['form'] = 'Too many sign-up attempts from your network. Please try again in '
            . throttle_wait_text(throttle_seconds_left($key)) . '.';
        $status = 429;
    } else {
        throttle_hit($key, 3600);

        // array_filter drops the nulls, leaving only fields that failed.
        $errors = array_filter([
            'name'     => validate_name($name),
            'email'    => validate_email($email),
            'password' => validate_password($password, $email),
            'password_confirmation' => $password !== $confirmation ? 'Passwords do not match.' : null,
        ]);

        if (!$errors) {
            $userId = auth_register($name, $email, $password);

            if ($userId === null) {
                // A SaaS trade-off: telling people the email is taken is
                // friendlier, but reveals that the address has an account.
                // The rate limit above keeps that from being harvested at
                // scale. If your users need stronger privacy, show a neutral
                // message and email the address owner instead.
                $errors['email'] = 'An account with this email already exists. Try signing in.';
            } else {
                auth_login(user_find_by_id($userId));
                flash('success', 'Welcome aboard! Your account is ready.');
                redirect('dashboard.php');
            }
        }
        $status = 422;
    }
}

view('pages/register', [
    'title'     => 'Create account',
    'errors'    => $errors,
    'old'       => $old,
    'minLength' => (int) config('auth.password_min', 12),
], 'auth', $status);
