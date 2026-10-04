# Migrations

A migration is a small `.sql` file that changes the database after the first install: a new table, a new column, a new index. Each one runs exactly once.

The schema files (`database/schema.sqlite.sql`, `database/schema.mysql.sql`) are the frozen 1.0 baseline. Don't edit them. Every change after 1.0, Core's or yours, is a migration.

## Where they go

```text
database/migrations/
├── sqlite/    used when db.driver = 'sqlite'
└── mysql/     used when db.driver = 'mysql'
```

SQLite and MySQL spell things differently (`INTEGER PRIMARY KEY AUTOINCREMENT` against `INT UNSIGNED AUTO_INCREMENT`), so each migration is written twice, once per folder, under the same file name. If you only ever use one database, you only need that folder.

## Naming

Files run in alphabetical order, so the number decides the order.

| Prefix | Who writes it | Example |
|---|---|---|
| `core-` | Shipped with VanillaSaaS Core updates. Don't edit or rename. | `core-002-email-verification.sql` |
| `app-` | You, for your own tables. | `app-001-projects.sql` |

Never change a migration after it has run anywhere. If it was wrong, write a new one that corrects it. A database that already applied the old version will never run the file again, so an edit would leave your live site and a fresh install with different tables.

## Running them

```bash
php bin/install.php
```

It applies whatever hasn't been applied and records each file in the `migrations` table. Run it after adding a migration and after every Core update. A brand-new SQLite database runs them by itself on the first request.

No shell access? In phpMyAdmin, import the new files from `database/migrations/mysql/` in name order. End each file with the line below so the database remembers it ran, and `bin/install.php` skips it later:

```sql
INSERT INTO migrations (name, applied_at) VALUES ('app-001-projects', '2026-10-02 12:00:00');
```

Every `core-` migration already ends with that line.

## Example: your first table

`database/migrations/sqlite/app-001-projects.sql`

```sql
CREATE TABLE projects (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title       TEXT    NOT NULL,
    created_at  TEXT    NOT NULL,
    updated_at  TEXT    NOT NULL
);

CREATE INDEX idx_projects_user ON projects (user_id);
```

`database/migrations/mysql/app-001-projects.sql`

```sql
CREATE TABLE `projects` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED NOT NULL,
    `title`       VARCHAR(120) NOT NULL,
    `created_at`  DATETIME     NOT NULL,
    `updated_at`  DATETIME     NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_projects_user` (`user_id`),
    CONSTRAINT `fk_projects_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Two rules for the file itself: end every statement with a semicolon at the end of a line, and give every table that belongs to a user a `user_id` with `ON DELETE CASCADE`, so deleting an account deletes its data.
