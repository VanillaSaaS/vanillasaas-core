<?php
/**
 * =============================================================================
 *  app/views/pages/dashboard.php — THE SIGNED-IN HOME SCREEN
 * =============================================================================
 *  This is where YOUR product starts. Everything below is a placeholder built
 *  from the component classes in app.css (stat cards, panels, empty state),
 *  so you can see the building blocks in use and replace them.
 *
 *  Variables: $user (current user row), $stats (label => value),
 *             $showChecklist (bool), $profileReviewed (bool)
 * =============================================================================
 */
$firstName = explode(' ', trim($user['name']))[0];
?>
<section class="welcome">
    <h2>Welcome, <?= e($firstName) ?></h2>
    <p class="muted">Here's what's happening with your account.</p>
</section>

<!-- Stat cards: swap these for the numbers your product cares about. -->
<section class="stats" aria-label="Account summary">
    <?php foreach ($stats as $label => $value): ?>
        <div class="stat">
            <p class="stat-label"><?= e($label) ?></p>
            <p class="stat-value"><?= e($value) ?></p>
        </div>
    <?php endforeach; ?>
</section>

<?php /* One panel in production, two side by side while developing. */ ?>
<div class="<?= $showChecklist ? 'grid-2' : 'stack' ?>">
    <!-- Empty state: what a new user sees before they've created anything. -->
    <section class="panel">
        <header class="panel-header">
            <h3>Your projects</h3>
        </header>
        <div class="empty-state">
            <div class="empty-icon" aria-hidden="true"></div>
            <p><strong>Nothing here yet</strong></p>
            <p class="muted">This is where your product's main feature lives.</p>
            <?php if ($showChecklist): ?>
                <p class="small muted">Edit <code>app/views/pages/dashboard.php</code></p>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($showChecklist): ?>
        <!-- Developer checklist. Hidden automatically once app.env is
             'production' (see public/dashboard.php). Replace it with an
             onboarding list for your own users, driven by real data the same
             way $profileReviewed is. -->
        <section class="panel">
            <header class="panel-header">
                <h3>Getting started</h3>
                <p class="muted">Only visible in local mode.</p>
            </header>
            <ol class="checklist">
                <li class="is-done">Create your account</li>
                <?php if ($profileReviewed): ?>
                    <li class="is-done">Update your profile or password</li>
                <?php else: ?>
                    <li><a href="settings.php">Update your profile or password</a></li>
                <?php endif; ?>
                <li>Add your first feature (see <code>AI-PROMPTS.md</code>)</li>
                <li>Set <code>app.env</code> to <code>production</code> before launch (this panel then disappears)</li>
            </ol>
        </section>
    <?php endif; ?>
</div>
