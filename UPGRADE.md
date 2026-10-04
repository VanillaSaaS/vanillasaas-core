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
2. **New migrations** (`database/migrations/*/core-*.sql`): copy them in, then run `php bin/install.php`. No shell access: import the new `core-` files from `database/migrations/mysql/` in phpMyAdmin, in name order. Each one records itself, so it can't run twice.
3. **Files you own that the release also changed** (a page in `public/`, a template in `app/views/`, a new setting in `app/config.php`): don't copy these over, you'd lose your work. The changelog shows the exact lines that changed, before and after. Make the same edit in your copy by hand.
4. **Check it.** Register a test account, sign out, sign in, request a password reset, change the password. If anything fails, look in `storage/logs/app.log`.

Never copy `storage/`, `app/config.local.php` or `app/custom.php` from a release over yours. Releases don't contain your data or settings, and overwriting them is how a live site gets wiped.

## If you edited a file in `app/lib/`

Then step 1 would erase your change. Before copying, compare your file with the one from the release you originally started from, to see what you changed. Move that change into `app/custom.php` as your own function, point your pages at it, and then take Core's new file as it is. It's a one-off cost, and updates are simple from then on.

## Security fixes

A security fix is published on GitHub and emailed to the VanillaSaaS update list as soon as it's released, and marked **Security** in the changelog with how serious it is and which files to replace. Apply those the day they arrive. A site left on a version with a published flaw is the easiest kind to attack, because the flaw is now public.

---

## Release notes for upgraders

### 1.0.0

First release. Nothing to upgrade from.
