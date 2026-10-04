<?php
/**
 * =============================================================================
 *  app/views/pages/settings.php — PROFILE, PASSWORD, DELETE ACCOUNT
 * =============================================================================
 *  Three independent forms on one page. Each posts a hidden `action` field so
 *  public/settings.php knows which one was submitted, and each has its own
 *  error bag ($errors['profile'], $errors['password'], $errors['delete']).
 *
 *  Every field name is unique across the page (profile_password,
 *  current_password, delete_password) so element ids never collide.
 *
 *  Variables: $user, $errors, $old, $minLength
 * =============================================================================
 */
$pe = $errors['profile']  ?? [];
$pw = $errors['password'] ?? [];
$de = $errors['delete']   ?? [];
?>
<div class="settings">

    <!-- ===================== Profile ===================== -->
    <section class="panel" aria-labelledby="profile-heading">
        <header class="panel-header">
            <h2 id="profile-heading">Profile</h2>
            <p class="muted">Your name and the email you sign in with.</p>
        </header>

        <form method="post" action="settings.php" class="form panel-body" data-once>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="profile">

            <div class="field">
                <label for="name">Name</label>
                <input id="name" name="name" type="text" autocomplete="name" required maxlength="100"
                       value="<?= e($old['name'] ?? $user['name']) ?>"<?= field_attrs($pe, 'name') ?>>
                <?= field_error($pe, 'name') ?>
            </div>

            <div class="field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" inputmode="email" autocomplete="email" required maxlength="254"
                       value="<?= e($old['email'] ?? $user['email']) ?>"<?= field_attrs($pe, 'email') ?>>
                <?= field_error($pe, 'email') ?>
            </div>

            <div class="field">
                <label for="profile_password">Current password <span class="muted">(only needed to change your email)</span></label>
                <input id="profile_password" name="profile_password" type="password" autocomplete="current-password"
                       <?= field_attrs($pe, 'profile_password') ?>>
                <?= field_error($pe, 'profile_password') ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save profile</button>
            </div>
        </form>
    </section>

    <!-- ===================== Password ===================== -->
    <section class="panel" aria-labelledby="password-heading">
        <header class="panel-header">
            <h2 id="password-heading">Password</h2>
            <p class="muted">Changing it signs you out on every other device.</p>
        </header>

        <form method="post" action="settings.php" class="form panel-body" data-once>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="password">
            <input type="email" name="username" value="<?= e($user['email']) ?>" autocomplete="username" hidden readonly>

            <div class="field">
                <label for="current_password">Current password</label>
                <input id="current_password" name="current_password" type="password" autocomplete="current-password" required
                       <?= field_attrs($pw, 'current_password') ?>>
                <?= field_error($pw, 'current_password') ?>
            </div>

            <div class="field">
                <label for="new_password">New password</label>
                <div class="password-wrap">
                    <input id="new_password" name="new_password" type="password" autocomplete="new-password" required
                           minlength="<?= e($minLength) ?>"<?= field_attrs($pw, 'new_password') ?>>
                </div>
                <p class="hint">At least <?= e($minLength) ?> characters.</p>
                <?= field_error($pw, 'new_password') ?>
            </div>

            <div class="field">
                <label for="new_password_confirmation">Confirm new password</label>
                <input id="new_password_confirmation" name="new_password_confirmation" type="password" autocomplete="new-password" required
                       <?= field_attrs($pw, 'new_password_confirmation') ?>>
                <?= field_error($pw, 'new_password_confirmation') ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update password</button>
            </div>
        </form>
    </section>

    <!-- ===================== Danger zone ===================== -->
    <section class="panel panel-danger" aria-labelledby="delete-heading">
        <header class="panel-header">
            <h2 id="delete-heading">Delete account</h2>
            <p class="muted">Permanently removes your account and all its data. This cannot be undone.</p>
        </header>

        <form method="post" action="settings.php" class="form panel-body" data-once
              data-confirm="Delete your account permanently? This cannot be undone.">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">

            <div class="field">
                <label for="delete_password">Enter your password to confirm</label>
                <input id="delete_password" name="delete_password" type="password" autocomplete="current-password" required
                       <?= field_attrs($de, 'delete_password') ?>>
                <?= field_error($de, 'delete_password') ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-danger">Delete my account</button>
            </div>
        </form>
    </section>
</div>
