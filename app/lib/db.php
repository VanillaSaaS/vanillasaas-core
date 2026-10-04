<?php
/**
 * =============================================================================
 *  app/lib/db.php — ONE DATABASE CONNECTION, TWO ENGINES
 * =============================================================================
 *
 *  CORE FILE — replaced by VanillaSaaS Core updates. Don't edit it; put your
 *  own functions in app/custom.php so updates stay a simple file swap.
 *
 *  db()        → the shared PDO connection (opened on first use)
 *  db_one()    → first row of a query, or null
 *  db_all()    → all rows
 *  db_run()    → INSERT/UPDATE/DELETE; returns number of affected rows
 *
 *  THE ONLY RULE THAT MATTERS: NEVER BUILD SQL WITH STRING CONCATENATION.
 *
 *      BAD:  db_one("SELECT * FROM users WHERE email = '$email'");
 *      GOOD: db_one('SELECT * FROM users WHERE email = ?', [$email]);
 *
 *  With `?` placeholders the database receives the SQL and the values
 *  separately, so a value can never be interpreted as SQL. That is the
 *  complete cure for SQL injection.
 * =============================================================================
 */

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $options = [
        // Throw exceptions on SQL errors instead of failing silently.
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        // Rows come back as ['column' => value] arrays.
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Use the database's real prepared statements rather than PHP
        // emulating them. Real ones keep values and SQL fully separate.
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $driver = config('db.driver');

    if ($driver === 'sqlite') {
        $path = (string) config('db.sqlite.path');
        $pdo  = new PDO('sqlite:' . $path, null, null, $options);

        // SQLite ships with foreign keys OFF for backwards compatibility.
        // Turn them on so deleting a user also deletes their reset tokens.
        $pdo->exec('PRAGMA foreign_keys = ON');
        // Write-Ahead Logging: readers don't block the writer. Big win for a
        // web app where many requests read at once.
        $pdo->exec('PRAGMA journal_mode = WAL');
        // If the database is momentarily locked, wait up to 5s instead of
        // failing immediately with "database is locked".
        $pdo->exec('PRAGMA busy_timeout = 5000');

        db_install_if_needed($pdo);
        return $pdo;
    }

    if ($driver === 'mysql') {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            config('db.mysql.host'),
            (int) config('db.mysql.port', 3306),
            config('db.mysql.name'),
            config('db.mysql.charset', 'utf8mb4')
        );
        $pdo = new PDO($dsn, (string) config('db.mysql.user'), (string) config('db.mysql.pass'), $options);

        // Store and compare timestamps in UTC regardless of server settings.
        $pdo->exec("SET time_zone = '+00:00'");
        return $pdo;
    }

    throw new RuntimeException("Unknown db.driver '{$driver}'. Use 'sqlite' or 'mysql'.");
}

/** Run a query and return the first row, or null if there isn't one. */
function db_one(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

/** Run a query and return every row. */
function db_all(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Run an INSERT/UPDATE/DELETE and return how many rows it changed. */
function db_run(string $sql, array $params = []): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Run several queries as one all-or-nothing unit.
 *
 *   db_transaction(function () { ...two writes that must both succeed... });
 *
 * If anything throws, every change inside is rolled back.
 */
function db_transaction(callable $work): mixed
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $result = $work();
        $pdo->commit();
        return $result;
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}

/**
 * SQLite only: create the tables on first run so `php -S` "just works".
 * For MySQL you run bin/install.php (or import the .sql file) once, because
 * a production database user usually shouldn't have CREATE privileges.
 */
function db_install_if_needed(PDO $pdo): void
{
    $exists = $pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'users'")->fetchColumn();
    if (!$exists) {
        db_run_sql_file($pdo, APP_ROOT . '/database/schema.sqlite.sql');
        db_migrate($pdo);
    }
}

/**
 * Apply every migration that hasn't been applied yet. Returns the names of
 * all migrations applied during this request or command.
 *
 * WHAT A MIGRATION IS
 * A small .sql file that changes the database after the first install: a new
 * table, a new column. They live in database/migrations/<driver>/ and run in
 * alphabetical order, each exactly once. Two families share the folder:
 *
 *   core-002-something.sql   shipped with a VanillaSaaS Core update
 *   app-001-projects.sql     written by you, for your own tables
 *
 * HOW "EXACTLY ONCE" WORKS
 * The `migrations` table lists every file already applied. We skip those,
 * run the rest, and record each one. The schema files are the frozen 1.0
 * baseline and are never edited again; every later change is a migration.
 * That is what lets an old install and a brand-new one end up identical.
 *
 * Run by `php bin/install.php`, and automatically when a new SQLite database
 * is created.
 */
function db_migrate(PDO $pdo): array
{
    // Installs made before this table existed get it here.
    $pdo->exec('CREATE TABLE IF NOT EXISTS migrations (
        name        VARCHAR(190) NOT NULL PRIMARY KEY,
        applied_at  VARCHAR(19)  NOT NULL
    )');

    $files = glob(APP_ROOT . '/database/migrations/' . config('db.driver') . '/*.sql') ?: [];
    sort($files);

    // Remembered for the life of this PHP process, so the installer can report
    // migrations that a brand-new SQLite database applied on connecting.
    static $applied = [];

    $record  = $pdo->prepare('INSERT INTO migrations (name, applied_at) VALUES (?, ?)');
    $isDone  = $pdo->prepare('SELECT 1 FROM migrations WHERE name = ?');

    foreach ($files as $file) {
        $name = basename($file, '.sql');

        $isDone->execute([$name]);
        if ($isDone->fetchColumn()) {
            continue;
        }

        db_run_sql_file($pdo, $file);

        // A migration may record itself (so it also works when imported by
        // hand in phpMyAdmin). Only add the row if it didn't.
        $isDone->execute([$name]);
        if (!$isDone->fetchColumn()) {
            $record->execute([$name, now_utc()]);
        }
        $applied[] = $name;
    }
    return $applied;
}

/**
 * Execute a .sql file statement by statement.
 * Statements are split on a semicolon at the end of a line; keep that
 * convention in the schema files.
 */
function db_run_sql_file(PDO $pdo, string $file): void
{
    $sql = (string) file_get_contents($file);
    // Strip "-- comment" lines so they don't confuse the splitter.
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);

    foreach (preg_split('/;\s*$/m', (string) $sql) as $statement) {
        if (trim($statement) !== '') {
            $pdo->exec($statement);
        }
    }
}
