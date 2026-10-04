<?php
/**
 * =============================================================================
 *  public/logout.php — SIGN OUT (POST ONLY)
 * =============================================================================
 *  Why POST + CSRF token instead of a simple link?
 *  If logout worked on GET, any website could sign your users out by
 *  embedding <img src="https://your-app.com/logout.php">. A nuisance, and a
 *  building block for nastier attacks. The sign-out button in the sidebar is
 *  a tiny form for exactly this reason.
 * =============================================================================
 */

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

allow_methods('POST');
csrf_verify();

auth_logout();
flash('success', "You've been signed out.");
redirect('login.php');
