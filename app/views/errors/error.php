<?php
/**
 * app/views/errors/error.php — shared page for 400/403/404/405/429/500 etc.
 *
 * Variables: $code, $title, $message, $debug (exception text; local env only)
 */
?>
<div class="error-page">
    <p class="error-code"><?= e($code) ?></p>
    <h1><?= e($title) ?></h1>
    <p class="muted"><?= e($message) ?></p>

    <p class="error-actions">
        <a class="btn btn-primary" href="index.php">Go to the home page</a>
    </p>

    <?php if (!empty($debug)): ?>
        <!-- Shown only when app.env is not 'production'. -->
        <details class="debug" open>
            <summary>Debug details (hidden in production)</summary>
            <pre><?= e($debug) ?></pre>
        </details>
    <?php endif; ?>
</div>
