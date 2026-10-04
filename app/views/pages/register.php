<?php
/**
 * app/views/pages/register.php — sign-up form.
 *
 * Variables: $errors, $old (['name' => ..., 'email' => ...]), $minLength
 *
 * We never re-fill password fields after an error: the value would sit in
 * the page source, and browsers may cache it.
 */
?>
<h1 class="card-title">Create your account</h1>
<p class="card-subtitle">It takes less than a minute.</p>

<?php if (!empty($errors['form'])): ?>
    <div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div>
<?php endif; ?>

<form method="post" action="register.php" class="form" data-once>
    <?= csrf_field() ?>

    <div class="field">
        <label for="name">Your name</label>
        <input id="name" name="name" type="text" autocomplete="name" required maxlength="100" autofocus
               value="<?= e($old['name'] ?? '') ?>"<?= field_attrs($errors, 'name') ?>>
        <?= field_error($errors, 'name') ?>
    </div>

    <div class="field">
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" inputmode="email" autocomplete="email" required maxlength="254"
               value="<?= e($old['email'] ?? '') ?>"<?= field_attrs($errors, 'email') ?>>
        <?= field_error($errors, 'email') ?>
    </div>

    <div class="field">
        <label for="password">Password</label>
        <div class="password-wrap">
            <input id="password" name="password" type="password" autocomplete="new-password" required
                   minlength="<?= e($minLength) ?>"<?= field_attrs($errors, 'password') ?>>
        </div>
        <p class="hint">At least <?= e($minLength) ?> characters. A few random words is strong and easy to remember.</p>
        <?= field_error($errors, 'password') ?>
    </div>

    <div class="field">
        <label for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
               <?= field_attrs($errors, 'password_confirmation') ?>>
        <?= field_error($errors, 'password_confirmation') ?>
    </div>

    <button type="submit" class="btn btn-primary btn-block">Create account</button>
</form>

<p class="card-footer">Already have an account? <a href="login.php">Sign in</a></p>
