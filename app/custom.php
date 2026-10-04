<?php
/**
 * =============================================================================
 *  app/custom.php — YOUR FUNCTIONS GO HERE
 * =============================================================================
 *
 *  This file belongs to you. VanillaSaaS Core updates never touch it.
 *
 *  WHY IT EXISTS
 *  -------------
 *  Everything in app/lib/ is Core's code. When a bug or security fix is
 *  released, you apply it by replacing files in app/lib/. If you had edited
 *  those files, the replacement would wipe out your changes — so instead of
 *  editing them, add your own functions here. It is loaded on every request,
 *  after all of Core's libraries, so every Core helper is available.
 *
 *  When this file gets long, split it up and require the pieces from here:
 *
 *      require __DIR__ . '/custom/projects.php';
 *      require __DIR__ . '/custom/billing.php';
 *
 *  RUNNING CODE ON EVERY REQUEST
 *  -----------------------------
 *  Define a function called app_boot() here and Core calls it on every web
 *  request, after the session has started and before the page runs:
 *
 *      function app_boot(): void
 *      {
 *          // e.g. sign the visitor in from a "remember me" cookie
 *      }
 *
 *  EXAMPLE
 *  -------
 *      function project_find(int $id): ?array
 *      {
 *          // Always scope to the signed-in user (see README, "Three rules").
 *          return db_one('SELECT * FROM projects WHERE id = ? AND user_id = ?', [$id, auth_id()]);
 *      }
 * =============================================================================
 */

declare(strict_types=1);
