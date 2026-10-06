# Updating VanillaSaaS Core

Core is a starting point you own, not a package that updates itself. Once you've built on it, a fix reaches your app only when you apply it. This file is how.

## What kind of release is it?

| Release | Contains | What you do |
|---|---|---|
| **1.0.x** (patch) | Bug and security fixes only | Apply it. Usually a straight replacement of files in `app/lib/`. |
| **1.x** (minor) | New features, plus all earlier fixes | Optional. Apply it if you want the feature. |
| **2.0** (major) | Changes that break existing apps | A separate product. Your 1.x app keeps working as it is. |

Fixes are made to the latest 1.x release only. If you're several releases behind, apply them in order, oldest first.

Your current release is in the `VERSION` file. `php bin/install.php` prints it too.

## Before you start

1. **Back up.** Copy the project folder and the database. With SQLite that's the file in `storage/database/`; with MySQL, export from phpMyAdmin.
2. **Read the release's entry in `CHANGELOG.md`.** Every entry lists the files that changed and says whether there's a database migration.
3. **Do it on your own computer first**, check that sign-in still works, then repeat on the live site.

## Applying an update

Unzip the new release next to your project, not on top of it. Then, working from the changelog's file list:

1. **Core's files** (`app/lib/`, `app/bootstrap.php`, `bin/`, `VERSION`, and the documentation): copy them over yours. These were never yours to edit, so nothing is lost.
2. **New migrations** (`database/migrations/*/core-*.sql`): copy them in. SQLite applies them on the next request that uses the database. MySQL: run `php bin/install.php`, or with no shell access import the new `core-` files from `database/migrations/mysql/` in phpMyAdmin, in name order. Each one records itself, so it can't run twice.
3. **Files you own that the release also changed** (a page in `public/`, a template in `app/views/`, a new setting in `app/config.php`): don't copy these over, you'd lose your work. The changelog shows the exact lines that changed, before and after. Make the same edit in your copy by hand.
4. **Check it.** Register a test account, sign out, sign in, request a password reset, change the password. If anything fails, look in `storage/logs/app.log`.

Never copy `storage/`, `app/config.local.php` or `app/custom.php` from a release over yours. Releases don't contain your data or settings, and overwriting them is how a live site gets wiped.

## If you edited a file in `app/lib/`

Then step 1 would erase your change. Before copying, compare your file with the one from the release you originally started from, to see what you changed. Move that change into `app/custom.php` as your own function, point your pages at it, and then take Core's new file as it is. It's a one-off cost, and updates are simple from then on.

## No command line on your host?

Many shared hosts give you a file manager and phpMyAdmin and nothing else. Core is built to work there.

- **SQLite:** there is nothing to run. Tables are created on the first request and new migration files apply themselves.
- **MySQL:** import the `.sql` files in phpMyAdmin, as described in the steps above.
- **Any script in `bin/`:** almost every host has a Cron Jobs screen even when it has no terminal. Schedule `php /home/youruser/your-app/bin/install.php` for two minutes from now, let it run once, then delete the job. The output is emailed to your hosting account's address.

## Security fixes

A security fix is published on GitHub and emailed to the VanillaSaaS update list as soon as it's released, and marked **Security** in the changelog with how serious it is and which files to replace. Apply those the day they arrive. A site left on a version with a published flaw is the easiest kind to attack, because the flaw is now public.

---

## Release notes for upgraders

### 1.0.1

Replace `app/lib/db.php`, `bin/install.php` and `VERSION`. No migration, no change to your own files.

After this, a SQLite app applies new migration files by itself. Before you copy the files to a live SQLite site, look in `database/migrations/sqlite/`: anything there that hasn't been applied yet will be, on the next request that uses the database.

### 1.0.0

First release. Nothing to upgrade from.
