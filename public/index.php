<?php
/**
 * =============================================================================
 *  public/index.php — PUBLIC HOME PAGE
 * =============================================================================
 *  A minimal landing page for your product. Replace the copy in
 *  app/views/pages/home.php, or point this at a full marketing page.
 * =============================================================================
 */

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

allow_methods('GET');

// Unknown addresses are sent to public/404.php by .htaccess. PHP's built-in
// server has no .htaccess and sends them here instead, so check: anything
// other than the home page itself is a 404.
if (!in_array(request_path(), ['', 'index.php'], true)) {
    abort(404);
}

view('pages/home', [
    'title'    => 'Welcome',
    'signedIn' => auth_user() !== null,
], 'auth');
