<?php
/**
 * =============================================================================
 *  app/views/layouts/auth.php — LAYOUT FOR SIGNED-OUT PAGES
 * =============================================================================
 *  A centred card: sign in, register, password reset, error pages.
 *
 *  Variables:
 *    $title    page title (string)
 *    $content  the rendered page, injected by view()
 *
 *  Note: no inline <script> or style="" anywhere. Our Content-Security-Policy
 *  blocks them (deliberately — see app/lib/http.php).
 * =============================================================================
 */
$appName = (string) config('app.name');
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
    <title><?= e($title ?? $appName) ?> · <?= e($appName) ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body class="layout-auth">
    <a class="skip-link" href="#main">Skip to content</a>

    <header class="auth-header">
        <a class="brand" href="index.php">
            <span class="brand-mark" aria-hidden="true"></span>
            <?= e($appName) ?>
        </a>
    </header>

    <main id="main" class="auth-main">
        <div class="auth-card">
            <?= partial('flash') ?>
            <?= $content /* already escaped by the page template */ ?>
        </div>
    </main>

    <footer class="auth-footer">
        &copy; <?= date('Y') ?> <?= e($appName) ?>
    </footer>
</body>
</html>
