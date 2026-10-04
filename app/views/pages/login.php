<?php
/**
 * app/views/pages/login.php — sign-in form.
 *
 * Variables: $errors (field => message, plus 'form' for general errors), $email
 *
 * autocomplete="email" / "current-password" let password managers fill the
 * form correctly. Small detail, big difference to real users.
 */
?>
<h1 class="card-title">Sign in</h1>
<p class="card-subtitle">Welcome back. Enter your details to continue.</p>

<?php if (!empty($errors['form'])): ?>
    <div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div>
<?php endif; ?>

<form method="post" action="login.php" class="form" data-once>
    <?= csrf_field() ?>

    <div class="field">
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" inputmode="email"
               autocomplete="email" required maxlength="254" autofocus
               value="<?= e($email) ?>"<?= field_attrs($errors, 'email') ?>>
        <?= field_error($errors, 'email') ?>
    </div>

    <div class="field">
        <div class="label-row">
            <label for="password">Password</label>
            <a class="small" href="forgot-password.php">Forgot password?</a>
        </div>
        <div class="password-wrap">
            <input id="password" name="password" type="password"
                   autocomplete="current-password" required<?= field_attrs($errors, 'password') ?>>
        </div>
        <?= field_error($errors, 'password') ?>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Sign in</button>
</form>

<p class="card-footer">New here? <a href="register.php">Create an account</a></p>
