<?php
/**
 * =============================================================================
 *  app/views/layouts/app.php — LAYOUT FOR SIGNED-IN PAGES
 * =============================================================================
 *  Sidebar navigation + top bar + content area. On phones the sidebar
 *  becomes a horizontal strip above the content (pure CSS, no JS needed).
 *
 *  Variables:
 *    $title    page title
 *    $active   which nav item to highlight: 'dashboard' | 'settings' | ...
 *    $content  the rendered page, injected by view()
 *
 *  ADDING A NAV ITEM: add an entry to $nav below and create the page in
 *  /public. That's it.
 * =============================================================================
 */
$appName = (string) config('app.name');
$user    = auth_user();
$active  = $active ?? '';

// Simple inline SVG icons (24×24, stroke-based). Swap for your own set.
$icons = [
    'dashboard' => '<path d="M3 3h7v9H3zM14 3h7v5h-7zM14 12h7v9h-7zM3 16h7v5H3z"/>',
    'settings'  => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
    'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
];
$icon = static fn (string $name): string =>
    '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $icons[$name] . '</svg>';

$nav = [
    'dashboard' => ['label' => 'Dashboard', 'href' => 'dashboard.php'],
    'settings'  => ['label' => 'Settings',  'href' => 'settings.php'],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <?php if (!empty($baseHref)): ?>
        <!-- Set only on error pages: makes the relative links below resolve
             from the app's own folder, whatever address the error is shown at. -->
        <base href="<?= e($baseHref) ?>">
    <?php endif; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e($title ?? 'Dashboard') ?> · <?= e($appName) ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body class="layout-app">
    <a class="skip-link" href="#main">Skip to content</a>

    <div class="app-shell">
        <aside class="sidebar">
            <a class="brand" href="dashboard.php">
                <span class="brand-mark" aria-hidden="true"></span>
                <?= e($appName) ?>
            </a>

            <nav class="sidebar-nav" aria-label="Main">
                <?php foreach ($nav as $key => $item): ?>
                    <a href="<?= e($item['href']) ?>"
                       class="nav-link<?= $key === $active ? ' is-active' : '' ?>"
                       <?= $key === $active ? 'aria-current="page"' : '' ?>>
                        <?= $icon($key) ?>
                        <span><?= e($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Sign-out is a POST form, never a plain link: a link could be
                 triggered by any other site (<img src=".../logout.php">). -->
            <form class="sidebar-logout" method="post" action="logout.php">
                <?= csrf_field() ?>
                <button type="submit" class="nav-link nav-button">
                    <?= $icon('logout') ?>
                    <span>Sign out</span>
                </button>
            </form>
        </aside>

        <div class="app-main">
            <header class="topbar">
                <h1 class="topbar-title"><?= e($title ?? '') ?></h1>
                <?php if ($user): ?>
                    <div class="topbar-user">
                        <span class="avatar" aria-hidden="true"><?= e(initial($user['name'])) ?></span>
                        <span class="topbar-user-text">
                            <span class="topbar-user-name"><?= e($user['name']) ?></span>
                            <span class="topbar-user-email"><?= e($user['email']) ?></span>
                        </span>
                    </div>
                <?php endif; ?>
            </header>

            <main id="main" class="content">
                <?= partial('flash') ?>
                <?= $content ?>
            </main>
        </div>
    </div>
</body>
</html>
