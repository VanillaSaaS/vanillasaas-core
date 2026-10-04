# Changelog

Every entry says what changed, which files changed, and whether the database changed, so you can apply it to an app you've already built. The steps are in `UPGRADE.md`.

How to read an entry:

- **Security** entries state how serious the problem is. Apply them straight away.
- **Files** lists every changed file. Files in `app/lib/` are replaced whole. For a file you own (`public/`, `app/views/`, `app/config.php`), the entry shows the lines before and after, so you can make the same edit by hand.
- **Database** names any new migration. "None" means there's nothing to run.

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
