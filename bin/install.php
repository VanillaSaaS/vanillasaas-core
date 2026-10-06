<?php
/**
 * =============================================================================
 *  bin/install.php — CREATE AND UPDATE THE DATABASE TABLES
 * =============================================================================
 *  Usage (from the project folder):
 *
 *      php bin/install.php
 *
 *  Uses the database settings in app/config.php (+ config.local.php).
 *
 *  It does two things, and is safe to run as often as you like:
 *    1. Creates the 1.0 baseline tables if they don't exist.
 *    2. Applies any migration in database/migrations/<driver>/ that hasn't
 *       been applied yet — Core's (core-*.sql) and your own (app-*.sql).
 *
 *  RUN IT (MySQL): once after install, and again after every Core update or
 *  whenever you add a migration.
 *
 *  SQLite never needs it. The tables are created on the first request, and a
 *  new migration file is applied on the next request after you upload it.
 *  Running this is still harmless, and prints what is installed.
 *
 *  No shell access on a MySQL host? Import database/schema.mysql.sql in
 *  phpMyAdmin, then each new migration file in name order. Or run this
 *  script once from your host's Cron Jobs screen. See UPGRADE.md.
 * =============================================================================
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../app/bootstrap.php';

$driver = (string) config('db.driver');
echo "VanillaSaaS Core " . CORE_VERSION . " installer\n";
echo "Database driver: {$driver}\n";

try {
    $pdo = db();   // for SQLite this already creates the tables
    if ($driver === 'mysql') {
        db_run_sql_file($pdo, APP_ROOT . '/database/schema.mysql.sql');
    }
    $applied = db_migrate($pdo);
} catch (PDOException $ex) {
    fwrite(STDERR, "\nCould not connect or create tables:\n  " . $ex->getMessage() . "\n");
    fwrite(STDERR, "Check the 'db' settings in app/config.php or app/config.local.php.\n");
    exit(1);
}

$tables = ['users', 'password_resets', 'throttle', 'migrations'];
foreach ($tables as $table) {
    // Table names come from the fixed list above, never from user input.
    $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    echo "  ✓ {$table}\n";
}

echo $applied
    ? "\nMigrations applied:\n  + " . implode("\n  + ", $applied) . "\n"
    : "\nMigrations: nothing new to apply.\n";

echo "\nDone. Algorithm for new password hashes: "
    . (defined('PASSWORD_ARGON2ID') ? 'Argon2id' : 'bcrypt (Argon2id not available on this PHP build)')
    . "\n";
