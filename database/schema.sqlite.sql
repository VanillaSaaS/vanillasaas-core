-- =============================================================================
--  database/schema.sqlite.sql — SQLITE SCHEMA
-- =============================================================================
--  Applied automatically on the first request when db.driver = 'sqlite'.
--  Mirrors schema.mysql.sql: same tables, same columns.
--
--  Convention: one statement per block, each ending with a semicolon at the
--  end of a line (the installer splits on that).
-- =============================================================================

-- -----------------------------------------------------------------------------
-- users — one row per account.
-- -----------------------------------------------------------------------------
--  email COLLATE NOCASE: "Len@Example.com" and "len@example.com" are treated
--    as the same address, so nobody can register a near-duplicate. (We also
--    lower-case emails in PHP; this is the database-level safety net.)
--  password_hash: output of password_hash(). Never the password itself.
--  session_version: bumped on password change/reset. Every signed-in session
--    remembers the version it started with; a mismatch logs it out. This is
--    how "change password" signs you out on every other device.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    name             TEXT    NOT NULL,
    email            TEXT    NOT NULL COLLATE NOCASE UNIQUE,
    password_hash    TEXT    NOT NULL,
    session_version  INTEGER NOT NULL DEFAULT 1,
    last_login_at    TEXT    NULL,
    created_at       TEXT    NOT NULL,
    updated_at       TEXT    NOT NULL
);

-- -----------------------------------------------------------------------------
-- password_resets — single-use "forgot password" tokens.
-- -----------------------------------------------------------------------------
--  We store a SHA-256 HASH of the token, never the token itself. If this table
--  leaks, the attacker has hashes they can't turn back into working links.
--  ON DELETE CASCADE: deleting a user deletes their pending tokens.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_resets (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash  TEXT    NOT NULL UNIQUE,
    expires_at  TEXT    NOT NULL,
    created_at  TEXT    NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_password_resets_user ON password_resets (user_id);

-- -----------------------------------------------------------------------------
-- throttle — counters for rate limiting (login attempts, signups, resets).
-- -----------------------------------------------------------------------------
--  throttle_key: SHA-256 of e.g. "login|len@example.com|203.0.113.7". Hashed
--    so the table holds no raw emails or IPs (less personal data to protect).
--  reset_at: Unix timestamp when the counter expires.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS throttle (
    throttle_key  TEXT    PRIMARY KEY,
    hits          INTEGER NOT NULL,
    reset_at      INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_throttle_reset ON throttle (reset_at);

-- -----------------------------------------------------------------------------
-- migrations — which files in database/migrations/ have already been applied.
-- -----------------------------------------------------------------------------
--  This file is the frozen 1.0 baseline: never edit it. Every later change,
--  Core's or yours, is a new file in database/migrations/sqlite/.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS migrations (
    name        VARCHAR(190) NOT NULL PRIMARY KEY,
    applied_at  VARCHAR(19)  NOT NULL
);
