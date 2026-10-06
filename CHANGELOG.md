# Changelog

Every entry says what changed, which files changed, and whether the database changed, so you can apply it to an app you've already built. The steps are in `UPGRADE.md`.

How to read an entry:

- **Security** entries state how serious the problem is. Apply them straight away.
- **Files** lists every changed file. Files in `app/lib/` are replaced whole. For a file you own (`public/`, `app/views/`, `app/config.php`), the entry shows the lines before and after, so you can make the same edit by hand.
- **Database** names any new migration. "None" means there's nothing to run.

## 1.0.1 — 2026-10-06

**SQLite now applies new migrations by itself.** In 1.0.0 a SQLite database created its tables on the first request, but a migration added later needed `php bin/install.php`. On a host with no command line there was no way to run it, and SQLite has no phpMyAdmin to fall back on. Now a file in `database/migrations/sqlite/` is applied on the first request that uses the database after the file appears.

- All pending migrations run in one transaction. If any statement fails, nothing is changed, the error is shown and written to `storage/logs/app.log`, and the next request tries again once you've fixed the file.
- A file lock stops two simultaneous visitors applying the same migration twice.
- The check costs one folder listing and one small query per request.
- MySQL is unchanged: run `php bin/install.php` or import the file in phpMyAdmin.

One thing to check before you update: if `database/migrations/sqlite/` holds a file you have deliberately not applied, it will be applied. Move it out of the folder first.

**Files:** `app/lib/db.php` and `bin/install.php` (replace; the second is a comment change only), `VERSION`, `README.md`, `UPGRADE.md`, `database/migrations/README.md`. **Database:** none.

## 1.0.0 — 2026-10-02

First release of VanillaSaaS Core.

- Registration, sign-in, sign-out, forgot/reset password, account settings, account deletion
- Hardened sessions with idle and absolute timeouts; sign out other devices on password change
- CSRF protection, database-backed rate limiting, Argon2id hashing with bcrypt fallback
- Content-Security-Policy and security headers; private folders outside the web root
- SQLite (auto-installed) and MySQL/MariaDB schemas, with run-once migrations for later changes
- Responsive dashboard layout with automatic dark mode
- Core's code (`app/lib/`) kept separate from yours (`app/custom.php`, `public/`, `app/views/`) so updates are file replacements
- MIT licence

**Files:** all new. **Database:** baseline schema, no migrations.
