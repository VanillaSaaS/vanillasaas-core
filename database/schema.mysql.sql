-- =============================================================================
--  database/schema.mysql.sql — MYSQL / MARIADB SCHEMA
-- =============================================================================
--  Install with ONE of:
--    php bin/install.php                 (uses the credentials in your config)
--    phpMyAdmin → select your database → Import → choose this file
--
--  Requires MySQL 5.7+ or MariaDB 10.3+. Mirrors schema.sqlite.sql exactly.
--  Comments on each column's purpose live in schema.sqlite.sql.
--
--  Why these choices:
--    ENGINE=InnoDB         transactions + foreign keys (MyISAM has neither).
--    utf8mb4               real UTF-8; MySQL's "utf8" can't store emoji.
--    utf8mb4_unicode_ci    case-insensitive comparison, so the UNIQUE email
--                          index treats Len@x.com and len@x.com as equal.
--    VARCHAR(255) hash     password_hash() output is ~60 (bcrypt) to ~100
--                          (Argon2id) chars; 255 leaves room for the future.
--    DATETIME (UTC)        written by PHP in UTC, so no timezone surprises.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `users` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`             VARCHAR(100) NOT NULL,
    `email`            VARCHAR(254) NOT NULL,
    `password_hash`    VARCHAR(255) NOT NULL,
    `session_version`  INT UNSIGNED NOT NULL DEFAULT 1,
    `last_login_at`    DATETIME     NULL,
    `created_at`       DATETIME     NOT NULL,
    `updated_at`       DATETIME     NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED NOT NULL,
    `token_hash`  CHAR(64)     NOT NULL,
    `expires_at`  DATETIME     NOT NULL,
    `created_at`  DATETIME     NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_password_resets_token` (`token_hash`),
    KEY `idx_password_resets_user` (`user_id`),
    CONSTRAINT `fk_password_resets_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `throttle` (
    `throttle_key`  CHAR(64)     NOT NULL,
    `hits`          INT UNSIGNED NOT NULL,
    `reset_at`      INT UNSIGNED NOT NULL,
    PRIMARY KEY (`throttle_key`),
    KEY `idx_throttle_reset` (`reset_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Which files in database/migrations/mysql/ have already been applied.
-- This file is the frozen 1.0 baseline: never edit it. Every later change,
-- Core's or yours, is a new file in database/migrations/mysql/.
CREATE TABLE IF NOT EXISTS `migrations` (
    `name`        VARCHAR(190) NOT NULL,
    `applied_at`  VARCHAR(19)  NOT NULL,
    PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
