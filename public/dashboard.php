<?php
/**
 * =============================================================================
 *  public/dashboard.php — SIGNED-IN HOME (COPY THIS FILE TO ADD PAGES)
 * =============================================================================
 *  The smallest possible protected page:
 *
 *      require bootstrap  →  require_auth()  →  gather data  →  view()
 *
 *  require_auth() runs on the SERVER before any HTML is produced. A visitor
 *  who isn't signed in receives a redirect and zero bytes of this page —
 *  unlike JavaScript-only "protection", which still sends the page first.
 *
 *  TO ADD A NEW PAGE (e.g. "Projects"):
 *    1. Copy this file to public/projects.php
 *    2. Create app/views/pages/projects.php
 *    3. Change the view() call below to 'pages/projects', 'active' => 'projects'
 *    4. Add 'projects' to the $nav array in app/views/layouts/app.php
 * =============================================================================
 */

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

allow_methods('GET');
$user = require_auth();

// Time until the session expires (whichever timeout comes first), so users
// aren't surprised when they're signed out.
$idleLeft = (int) config('session.idle_timeout') - (time() - (int) $_SESSION['last_activity']);
$absLeft  = (int) config('session.absolute_timeout') - (time() - (int) $_SESSION['login_at']);
$minutes  = (int) floor(max(0, min($idleLeft, $absLeft)) / 60);

// The "Getting started" panel is a note to YOU, the developer. It is shown
// only while app.env is not 'production', so your customers never see it.
// Its second item ticks itself from real data: every profile or password
// save moves updated_at on from created_at, so "different" means "edited".
$showChecklist   = !is_production();
$profileReviewed = $user['updated_at'] !== $user['created_at'];

view('pages/dashboard', [
    'title'  => 'Dashboard',
    'active' => 'dashboard',
    'user'   => $user,
    'showChecklist'   => $showChecklist,
    'profileReviewed' => $profileReviewed,
    'stats'  => [
        'Member since'      => format_date($user['created_at']),
        // The sign-in BEFORE this one: an unexpected time here is a cheap
        // early warning that someone else has used the account.
        'Previous sign-in'  => format_date($_SESSION['previous_login_at'] ?? null, 'j M Y, H:i'),
        'Auto sign-out in'  => $minutes >= 60 ? floor($minutes / 60) . 'h ' . ($minutes % 60) . 'm' : "{$minutes}m",
    ],
]);
