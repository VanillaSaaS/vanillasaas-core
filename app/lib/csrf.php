<?php
/**
 * =============================================================================
 *  app/lib/csrf.php — CROSS-SITE REQUEST FORGERY PROTECTION
 * =============================================================================
 *
 *  CORE FILE — replaced by VanillaSaaS Core updates. Don't edit it; put your
 *  own functions in app/custom.php so updates stay a simple file swap.
 *
 *  THE ATTACK
 *  ----------
 *  You're signed in to your app. You visit evil.com, which contains a hidden
 *  form that auto-submits to your-app.com/settings.php changing your email.
 *  Your browser helpfully attaches your session cookie. Without protection,
 *  your app can't tell the difference between that and you clicking "Save".
 *
 *  THE DEFENCE
 *  -----------
 *  Every form we render includes a secret random token that lives in YOUR
 *  session. evil.com can make your browser send a request, but it can't READ
 *  our pages, so it can't know the token. No matching token → request refused.
 *
 *  HOW TO USE IT
 *  -------------
 *  In every <form method="post">:     <?= csrf_field() ?>
 *  At the top of every POST handler:  csrf_verify();
 * =============================================================================
 */

declare(strict_types=1);

/** The current session's token, created on first use. 32 random bytes = 256 bits. */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

/** A hidden <input> carrying the token. Echo it inside every POST form. */
function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

/**
 * Reject the request (HTTP 403 "Page expired") unless it carries the right token.
 *
 * The usual innocent cause is a form left open so long that the session
 * expired, so the message tells the user to refresh and retry.
 *
 * hash_equals() compares in constant time. A normal `===` stops at the first
 * wrong character, and an attacker measuring response times could in theory
 * recover the token one character at a time.
 */
function csrf_verify(): void
{
    $sent = input('_token');
    $real = $_SESSION['_csrf'] ?? '';

    if ($real === '' || $sent === '' || !hash_equals($real, $sent)) {
        abort(403, 'Your form expired for security reasons. Go back, refresh the page and try again.', 'Page expired');
    }
}

/** Issue a fresh token. Called at sign-in so a pre-login token can't be reused. */
function csrf_rotate(): void
{
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
}
