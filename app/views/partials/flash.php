<?php
/**
 * app/views/partials/flash.php — one-time messages queued with flash().
 *
 * role="status" makes screen readers announce success/info politely;
 * errors use role="alert", which interrupts — use it sparingly.
 */
foreach (flash_pull() as $flash):
    $type = in_array($flash['type'], ['success', 'error', 'info'], true) ? $flash['type'] : 'info';
?>
    <div class="alert alert-<?= e($type) ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>">
        <?= e($flash['message']) ?>
    </div>
<?php endforeach; ?>
