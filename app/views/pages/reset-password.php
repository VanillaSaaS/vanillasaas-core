<?php
/**
 * app/views/pages/reset-password.php — choose a new password from an email link.
 *
 * Variables: $valid (bool), $token, $email, $errors, $minLength
 *
 * The token travels in a hidden field on POST rather than staying in the
 * URL, so it isn't written to server access logs a second time.
 */
?>
<?php if (!$valid): ?>
    <h1 class="card-title">This link has expired</h1>
    <p class="card-subtitle">
        Reset links work once and expire after a short time.
        Request a new one and use the most recent email.
    </p>
    <a class="btn btn-primary btn-block" href="forgot-password.php">Request a new link</a>
<?php else: ?>
    <h1 class="card-title">Choose a new password</h1>
    <p class="card-subtitle">For <strong><?= e($email) ?></strong>. You'll be signed out of all devices.</p>

    <form method="post" action="reset-password.php" class="form" data-once>
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <!-- Hidden username field helps password managers save the new password
             against the right account. -->
        <input type="email" name="username" value="<?= e($email) ?>" autocomplete="username" hidden readonly>

        <div class="field">
            <label for="password">New password</label>
            <div class="password-wrap">
                <input id="password" name="password" type="password" autocomplete="new-password" required autofocus
                       minlength="<?= e($minLength) ?>"<?= field_attrs($errors, 'password') ?>>
            </div>
            <p class="hint">At least <?= e($minLength) ?> characters.</p>
            <?= field_error($errors, 'password') ?>
        </div>

        <div class="field">
            <label for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                   <?= field_attrs($errors, 'password_confirmation') ?>>
            <?= field_error($errors, 'password_confirmation') ?>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Save new password</button>
    </form>
<?php endif; ?>
