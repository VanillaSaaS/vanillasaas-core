<?php
/**
 * app/views/pages/forgot-password.php — request a reset link.
 * Variables: $errors, $email
 */
?>
<h1 class="card-title">Reset your password</h1>
<p class="card-subtitle">Enter your account's email and we'll send you a link to choose a new password.</p>

<?php if (!empty($errors['form'])): ?>
    <div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div>
<?php endif; ?>

<form method="post" action="forgot-password.php" class="form" data-once>
    <?= csrf_field() ?>

    <div class="field">
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" inputmode="email" autocomplete="email" required maxlength="254" autofocus
               value="<?= e($email) ?>"<?= field_attrs($errors, 'email') ?>>
        <?= field_error($errors, 'email') ?>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Send reset link</button>
</form>

<p class="card-footer"><a href="login.php">Back to sign in</a></p>
