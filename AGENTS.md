# Instructions for AI coding assistants

This file is read automatically by AI coding agents (GitHub Copilot agent mode in VS Code, Cursor, Codex, and Claude Code through `CLAUDE.md`). It tells them how this project is built and how to work in it.

It's yours: add notes about your own app under "This app" at the end, and keep them up to date as the app grows. Core updates never replace this file.

---

## What this project is

An app built on VanillaSaaS Core, a framework-free PHP 8.1+ starter: server-rendered pages, plain functions, SQLite or MySQL. No Composer, no npm, no build step, no framework. That is deliberate, not a gap to fill.

The developer may be new to this. Write code they can read, and explain anything that isn't obvious.

## Structure

```text
public/          web root. One PHP "page controller" per URL. The developer's to edit.
app/lib/         Core's functions, grouped by concern. NEVER EDIT: replaced by Core updates.
app/bootstrap.php  NEVER EDIT, same reason.
app/custom.php   the app's own functions, or files it requires (e.g. app/custom/invoices.php).
app/views/       templates: pages/, layouts/ (auth.php, app.php), partials/.
app/config.php   settings. app/config.local.php overrides it per machine and is not committed.
database/        schema.*.sql are a frozen baseline: never edit them.
  migrations/sqlite/  and  migrations/mysql/   every database change, one file per driver.
storage/         SQLite file, logs, sessions. Never inside public/.
bin/             command-line scripts. A browser gets a 404 from them on purpose.
```

## Page controller shape

Copy `public/dashboard.php` or `public/settings.php`:

1. `declare(strict_types=1); require __DIR__ . '/../app/bootstrap.php';`
2. `allow_methods('GET', 'POST');`
3. `$user = require_auth();` (or `require_guest();`)
4. `if (is_post()) { csrf_verify(); ... }` read with `input('field')`, validate into `$errors`; on success write with `db_run()`, `flash('success', '...')`, `redirect('page.php')`; otherwise `$status = 422;`
5. `view('pages/name', [...], 'app', $status);`

## Helpers available

`config('a.b')`, `e($v)`, `input($k, trim: false)`, `query($k)`, `redirect($to)`, `view()`, `partial()`, `abort($code, $msg)`, `flash($type, $msg)`, `db_one($sql, $params)`, `db_all()`, `db_run()`, `db_transaction(fn)`, `now_utc()`, `format_date()`, `auth_user()`, `auth_id()`, `require_auth()`, `csrf_field()`, `csrf_verify()`, `throttle_key()`, `throttle_too_many()`, `throttle_hit()`, `throttle_clear()`, `validate_email()`, `validate_name()`, `validate_password()`, `password_make()`, `mail_send($to, $subject, $body)`, `base_url()`, `field_error($errors, 'field')`, `field_attrs($errors, 'field')`, `log_message()`.

Read the function in `app/lib/` before using it. Don't guess its signature.

## Rules

- **SQL:** always `?` placeholders. Never put a variable inside an SQL string.
- **Ownership:** every query on user-owned data includes `AND user_id = ?` with `auth_id()`. A row belonging to someone else is a 404, not a 403.
- **Output:** every echoed value goes through `e()`.
- **Forms:** every POST form contains `<?= csrf_field() ?>`; every POST handler calls `csrf_verify()`.
- **No inline JavaScript or CSS:** no `<script>` blocks, no `onclick=""` or other `on*` attributes, no `style=""`. The Content-Security-Policy blocks them silently. JavaScript goes in `public/assets/js/`, CSS in `public/assets/css/app.css` using the existing variables and classes.
- **Dates:** generate with `now_utc()`, store as `Y-m-d H:i:s` in UTC.
- **Database changes are migrations.** A new file in BOTH `database/migrations/sqlite/` and `database/migrations/mysql/`, same name, `app-NNN-description.sql`, numbered after the last one. User-owned tables get `user_id` with a foreign key `ON DELETE CASCADE`. Never edit a migration that has already run: write a new one.
  - SQLite applies new migration files by itself, on the next request that uses the database.
  - MySQL needs `php bin/install.php`, or the file imported in phpMyAdmin.
- **New functions** go in `app/custom.php` or a file it requires, never in `app/lib/`. To change Core's behaviour, write a new function that wraps Core's. Code that must run on every request goes in `app_boot()` in `app/custom.php`.
- **No dependencies:** no Composer packages, no npm, no frameworks, no CDN scripts. Use PHP's standard library (PDO, curl, hash, random_bytes, sodium) and browser-native JavaScript.
- **Comments** follow the existing files: explain why, not just what.

## How to work

1. **Plan before code.** For any change bigger than a few lines, reply first with: the files you'll create or change, any migration, and anything you're unsure about. Wait for a go-ahead.
2. **One feature at a time.** If the request is several features, say so and do the first.
3. **Ask before running any terminal command.** Never delete files, drop tables or reset the database without being asked in so many words.
4. **When you finish, report:** every file you changed, the address to open to test it (the app runs at `http://localhost/<folder-name>/` on XAMPP), and what to try with a second account to prove one user can't see another's data.
5. **If something fails,** look in `storage/logs/app.log` before guessing. Emails aren't sent while developing: they're written to `storage/logs/mail.log`.
6. **If a rule above gets in the way,** say which rule and why, and stop. Don't work around it.

## Review checklist

When asked to review code, or before calling a feature finished, check for and quote every place that:

1. builds SQL with variables instead of `?` placeholders
2. reads or writes user-owned rows without `AND user_id = ?`
3. echoes a value without `e()`
4. has a POST form without `csrf_field()` or a handler without `csrf_verify()`
5. uses inline JavaScript, `on*` attributes or `style=""`
6. adds a dependency, framework or build step
7. edits a schema file, edits a migration that has run, or writes a migration for one driver but not the other
8. edits anything in `app/lib/` or `app/bootstrap.php`

Give the fix for each.

---

## This app

<!-- Describe your app here: what it does, its tables, its own functions and anything an assistant should know before changing it. -->
