<?php
/**
 * =============================================================================
 *  public/forgot-password.php — REQUEST A PASSWORD RESET LINK
 * =============================================================================
 *  The response is IDENTICAL whether or not the email has an account. That's
 *  deliberate: otherwise this form becomes a "which emails are registered?"
 *  lookup tool for attackers.
 * =============================================================================
 */

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

allow_methods('GET', 'POST');
require_guest();

$errors = [];
$email  = '';
$status = 200;

if (is_post()) {
    csrf_verify();

    $email = normalize_email(input('email'));

    // Limits: 3 links per email+IP and 10 per IP, every 15 minutes. Stops
    // anyone using your server to flood a victim's inbox.
    $emailKey = throttle_key('reset', $email, client_ip());
    $ipKey    = throttle_key('reset-ip', client_ip());

    if ($error = validate_email($email)) {
        $errors['email'] = $error;
        $status = 422;
    } elseif (throttle_too_many($emailKey, 3) || throttle_too_many($ipKey, 10)) {
        $errors['form'] = 'Too many reset requests. Please try again in '
            . throttle_wait_text(max(throttle_seconds_left($emailKey), throttle_seconds_left($ipKey))) . '.';
        $status = 429;
    } else {
        throttle_hit($emailKey, 900);
        throttle_hit($ipKey, 900);

        reset_request($email);

        flash('success', "If an account exists for {$email}, we've emailed a reset link. Check your inbox (and spam folder).");
        redirect('forgot-password.php');
    }
}

view('pages/forgot-password', [
    'title'  => 'Forgot password',
    'errors' => $errors,
    'email'  => $email,
], 'auth', $status);
