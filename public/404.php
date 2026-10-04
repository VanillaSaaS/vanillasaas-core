<?php
/**
 * =============================================================================
 *  public/404.php — "PAGE NOT FOUND" FOR ADDRESSES THAT MATCH NO FILE
 * =============================================================================
 *  A mistyped address such as /dashbord.php never reaches any of your pages,
 *  so without this the visitor would get the web server's bare default error
 *  page. public/.htaccess sends every request for a missing file here, and
 *  this shows the app's own styled 404 instead.
 *
 *  (When one of YOUR pages decides something doesn't exist, e.g. a project id
 *  that isn't the user's, call abort(404) there. Same page, same look.)
 * =============================================================================
 */

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

abort(404);
