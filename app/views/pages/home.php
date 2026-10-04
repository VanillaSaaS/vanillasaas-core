<?php
/**
 * app/views/pages/home.php — public landing page for YOUR product.
 *
 * Replace the copy with your own pitch. Variables: $signedIn (bool)
 */
$appName = (string) config('app.name');
?>
<div class="home">
    <h1 class="card-title"><?= e($appName) ?></h1>
    <p class="card-subtitle">
        One sentence that tells a visitor exactly what your product does and who it's for.
    </p>

    <?php if ($signedIn): ?>
        <a class="btn btn-primary btn-block" href="dashboard.php">Go to your dashboard</a>
    <?php else: ?>
        <div class="stack">
            <a class="btn btn-primary btn-block" href="register.php">Create a free account</a>
            <a class="btn btn-secondary btn-block" href="login.php">Sign in</a>
        </div>
    <?php endif; ?>
</div>
