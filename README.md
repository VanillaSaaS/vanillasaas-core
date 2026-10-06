# VanillaSaaS Core

A secure login system and dashboard in plain PHP, HTML and CSS, ready to build an app on. No Composer, no npm, no build step, no framework. Download it, run one command, and you have working sign-up, sign-in, password reset, account settings and a signed-in dashboard, on SQLite or MySQL.

It's written for a developer about to build a first app that other people will depend on: a members' area for a gym, a student portal for a tutor, a tool for a first paying client. On that job the login is the part you can't afford to get wrong, and the part nobody will thank you for.

Every file is commented to explain *why* it does what it does, not just what. Read it in the order listed under [How a request flows](#how-a-request-flows) and you'll understand the whole codebase in an afternoon, well enough to explain it to whoever is paying for the app.

> **Core is free and open source under the MIT licence.** It covers everything an app needs before it takes money: accounts, sessions, a dashboard and a database.
>
> **VanillaSaaS Pro** (in development) is the paid add-on for the point where you charge your own customers or manage other people's accounts: Stripe subscriptions, two-factor sign-in, Google and GitHub sign-in, roles, an admin area and teams. It installs on top of Core, so an app built on Core today doesn't need rebuilding. [Join the waitlist](https://vanillasaas.dev/#pricing).
>
> **Blueprints** are finished apps built on Core, sold as source code to deploy for clients. The first is [VanillaSaaS Bookings](https://vanillasaas.dev/blueprints/bookings): online booking for anyone who works by appointment.

---

## What's included

| Area | What you get |
|---|---|
| Accounts | Register, sign in, sign out, forgot/reset password, change name/email/password, delete account |
| Sessions | Hardened cookies, server-enforced idle (2h) and absolute (24h) timeouts, "sign out other devices" on password change |
| Security | CSRF tokens on every form, rate limiting on sign-in / sign-up / reset, Argon2id hashing (bcrypt fallback), Content-Security-Policy and other security headers, private files outside the web root |
| Database | One schema for SQLite (auto-installed) and one for MySQL/MariaDB, identical tables, plus run-once migrations for every change after that |
| UI | Responsive auth pages and a sidebar dashboard layout, automatic dark mode, accessible forms, styled tables, badges and form controls, one CSS file with design tokens |
| Ops | Error logging to `storage/logs`, styled error pages (including mistyped addresses) that hide details in production, one-command installer |
| Updates | Core's code kept apart from yours, a versioned changelog listing every changed file, and written upgrade steps |

Total: 35 small PHP files and two front-end files. Nothing to update, nothing to audit but your own code.

## Requirements

- PHP **8.1 or newer** with `pdo_sqlite` and/or `pdo_mysql` (standard on every mainstream host and on XAMPP)
- For production: Apache (cPanel/shared hosting) or Nginx, and HTTPS

---

## Quick start

### Option A — PHP's built-in server (any OS, 30 seconds)

```bash
cd vanillasaas-core
php -S localhost:8000 -t public
```

Open <http://localhost:8000> and create an account. The SQLite database is created in `storage/database/` on the first request.

`-t public` matters: it makes `/public` the web root, so nothing else in the project can be requested by a browser.

### Option B — XAMPP / MAMP / WAMP

1. Put the folder inside `htdocs` (e.g. `C:\xampp\htdocs\vanillasaas-core`).
2. Start Apache.
3. Open `http://localhost/vanillasaas-core/`.

The root `.htaccess` routes every request into `/public` automatically.

### Testing password reset locally

Mail is set to the `log` driver by default, so nothing is sent. Request a reset, then open `storage/logs/mail.log` and copy the link.

---

## Folder structure

```text
vanillasaas-core/
├── public/                  ← THE ONLY FOLDER BROWSERS CAN REACH
│   ├── index.php            public home page
│   ├── register.php         sign up
│   ├── login.php            sign in
│   ├── logout.php           sign out (POST only)
│   ├── forgot-password.php  request a reset link
│   ├── reset-password.php   choose a new password from the link
│   ├── dashboard.php        signed-in home — copy this to add pages
│   ├── settings.php         profile, password, delete account
│   ├── 404.php              styled "page not found" for mistyped addresses
│   └── assets/
│       ├── css/app.css      the whole design system
│       └── js/app.js        optional enhancements (app works without it)
│
├── app/
│   ├── bootstrap.php        loaded first by every page
│   ├── config.php           all settings, commented
│   ├── config.local.example.php
│   ├── custom.php           YOUR functions go here (never touched by updates)
│   ├── lib/                 Core's functions, one file per concern — don't edit
│   │   ├── helpers.php      config(), e(), redirect(), view(), input()
│   │   ├── errors.php       logging, error pages, abort()
│   │   ├── http.php         HTTPS detection, client IP, security headers
│   │   ├── db.php           PDO connection and query helpers
│   │   ├── session.php      hardened sessions, flash messages
│   │   ├── csrf.php         form tokens
│   │   ├── throttle.php     rate limiting
│   │   ├── validate.php     input rules
│   │   ├── auth.php         register, sign in, guards
│   │   ├── mail.php         outgoing email
│   │   └── password_reset.php
│   └── views/
│       ├── layouts/         auth.php (centred card), app.php (sidebar)
│       ├── pages/           one template per page
│       ├── partials/        flash messages
│       └── errors/          shared error page
│
├── database/
│   ├── schema.sqlite.sql    the 1.0 baseline — don't edit
│   ├── schema.mysql.sql     the 1.0 baseline — don't edit
│   └── migrations/          every database change after 1.0, yours and Core's
├── storage/                 writable: SQLite file, logs, sessions, cache
├── bin/install.php          creates tables and applies new migrations
├── VERSION                  which release you have
├── CHANGELOG.md             what changed in each release, file by file
├── UPGRADE.md               how to apply an update to an app you've built
├── LICENSE.md               what you can and can't do with Core
├── SECURITY.md              every defence, and the known limits
└── AI-PROMPTS.md            prompts for extending Core with an AI assistant
```

## How a request flows

Every page in `/public` has the same four steps. `public/login.php` is the clearest example:

1. **Bootstrap** — `require __DIR__ . '/../app/bootstrap.php';` loads config, error handling, security headers and the session.
2. **Guard** — `require_auth()` (signed-in only) or `require_guest()` (signed-out only). This runs on the server before any HTML exists, so a guest receives a redirect and zero bytes of the protected page.
3. **Handle POST** — `csrf_verify()`, read fields with `input()`, validate, write to the database with `db_run()`, then `redirect()`. On validation errors, fall through and re-render the form with messages.
4. **Render** — `view('pages/login', [...data...], 'auth')`.

Recommended reading order: `bootstrap.php` → `helpers.php` → `session.php` → `csrf.php` → `auth.php` → `public/login.php` → `public/settings.php`.

---

## Your files and Core's files

Once you download Core and start building, nothing updates your copy automatically. A fix reaches your app when you apply it. That is only painless if your code and Core's code live in different files, so the split is fixed from day one:

| Yours — edit freely | Core's — leave alone |
|---|---|
| `public/` (pages, CSS, JavaScript) | `app/lib/` |
| `app/views/` (templates, layouts) | `app/bootstrap.php` |
| `app/config.php`, `app/config.local.php` | `bin/install.php` |
| `app/custom.php`, your own scripts in `bin/` | `database/schema.*.sql` |
| `database/migrations/*/app-*.sql` | `database/migrations/*/core-*.sql` |

Almost every bug and security fix lands in `app/lib/`. If you never edit that folder, applying a fix means replacing a file. If you do edit it, every update becomes a manual merge, and the usual outcome is that the fix never gets applied.

Need Core to behave differently? Change a setting in `app/config.php`, or write your own function in `app/custom.php` and call that from your pages instead.

`UPGRADE.md` has the update steps. `VERSION` tells you which release you're on.

## Building your app

### Add a signed-in page

1. Copy `public/dashboard.php` to `public/projects.php`.
2. Create `app/views/pages/projects.php`.
3. In the new controller, change the view to `'pages/projects'` and `'active' => 'projects'`.
4. Add `'projects' => ['label' => 'Projects', 'href' => 'projects.php']` to `$nav` in `app/views/layouts/app.php` (and an icon to `$icons`).

### Add a database table

Write it as a migration: one small `.sql` file per database you use, with the same file name in each folder. Don't add it to the schema files; they are the frozen 1.0 baseline.

```sql
-- database/migrations/sqlite/app-001-projects.sql
CREATE TABLE projects (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title       TEXT    NOT NULL,
    created_at  TEXT    NOT NULL,
    updated_at  TEXT    NOT NULL
);
```

Then run `php bin/install.php`. It applies each migration once and remembers which ones it has run, so your laptop, your live site and a fresh install all end up with the same tables. The MySQL version of this example, and the rules for naming files, are in `database/migrations/README.md`.

Give every user-owned table a `user_id` with `ON DELETE CASCADE`, so deleting an account deletes its data.

**Always scope queries to the signed-in user.** This is the bug that leaks one customer's data to another:

```php
// WRONG — any signed-in user can read any project by changing ?id=
db_one('SELECT * FROM projects WHERE id = ?', [$id]);

// RIGHT
db_one('SELECT * FROM projects WHERE id = ? AND user_id = ?', [$id, auth_id()]);
```

### Three rules that keep it secure

1. Every value echoed into HTML goes through `e()`.
2. Every SQL value goes through a `?` placeholder. Never build SQL with string concatenation.
3. Every `<form method="post">` contains `<?= csrf_field() ?>`, and every POST handler calls `csrf_verify()`.

And one that keeps the Content-Security-Policy working: **no inline `<script>`, no `onclick=`, no `style=""`**. Put JavaScript in `public/assets/js/` and styles in `app.css`. The browser will refuse inline code by design.

---

## Going live — checklist

- [ ] **Point the domain's document root at `/public`.** On cPanel: Domains → your domain → Document Root. If your host won't allow it, upload the whole project and rely on the root `.htaccess` (Apache only).
- [ ] Set the app's name: `'app' => ['name' => ...]` and `'mail' => ['from_name' => ...]` in `app/config.php`. The default is "Your App".
- [ ] Create `app/config.local.php` from `config.local.example.php` with:
  - `'env' => 'production'`
  - `'url' => 'https://your-domain.com'` (required: reset links are built from it)
  - your MySQL credentials, if using MySQL
  - `'mail' => ['driver' => 'mail', 'from' => 'no-reply@your-domain.com']`
- [ ] **MySQL:** create the database, then `php bin/install.php`. No shell access: import `database/schema.mysql.sql` in phpMyAdmin, then each file in `database/migrations/mysql/` in name order.
- [ ] Make `/storage` and everything in it writable by the web server.
- [ ] Turn on HTTPS (Let's Encrypt is free on almost every host). Secure cookies and HSTS switch on automatically.
- [ ] **Prove the private folders are private.** Each of these must return 403 or 404, never a file:
  - `https://your-domain.com/app/config.php`
  - `https://your-domain.com/storage/database/app.sqlite`
  - `https://your-domain.com/storage/logs/app.log`
- [ ] Send yourself a password reset to confirm email delivery (check spam). If `mail()` is unreliable on your host, set `mail.driver` to `custom` and write `app_mail_send()` in `app/custom.php` to call your email provider's API.
- [ ] Back up the database daily (cPanel's backup tool, or copy the SQLite file while the site is quiet).
- [ ] Behind Cloudflare or a load balancer? Set `'security' => ['trust_proxy' => true]`, otherwise every visitor shares the proxy's IP for rate limiting.
- [ ] **Built for a client?** Write down what they get (the address and their own sign-in) and what you keep (the hosting login, the database password, the backups). Agree who renews the domain and the hosting before the day one of them lapses.

### Nginx

`.htaccess` files are ignored by Nginx, so the web root **must** be `/public`:

```nginx
server {
    server_name your-domain.com;
    root /var/www/vanillasaas-core/public;
    index index.php;

    # Unknown addresses get the app's own styled "Page not found".
    error_page 404 /404.php;

    location / {
        try_files $uri $uri/ =404;
    }

    location ~ \.php$ {
        try_files $uri =404;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\. { deny all; }
}
```

---

## What's deliberately not included

These are decisions that vary per app, so they're left out of Core rather than half-built in. Each has a ready-to-paste prompt in `AI-PROMPTS.md`. Two-factor sign-in, payments, teams, roles and the admin area are also being built and tested as VanillaSaaS Pro, for anyone who would rather not build them from a prompt:

- Email verification on sign-up
- "Remember me" persistent login
- Two-factor authentication
- Payments / subscriptions (Stripe)
- Teams, roles and an admin area
- Checking passwords against breach databases (Have I Been Pwned)

## Tested on

PHP 8.3 with the built-in server + SQLite, and Apache 2.4 (installed in a sub-folder, as on XAMPP) + MariaDB, covering 57 end-to-end checks: every flow above, CSRF rejection, rate limiting, session rotation, cross-device sign-out, single-use reset tokens, case-insensitive emails, HTML escaping, and private-file exposure.

## Help and bug reports

Found a bug in Core? Open an issue on GitHub with your Core version (it's in `VERSION`), your PHP version and the steps to reproduce it. Report a security flaw privately instead: see `SECURITY.md`.

Issues are for bugs in Core. Help with your own app is a paid service, the [Pre-launch Review](https://vanillasaas.dev/#review): a written review of your database design and first feature, then 14 days of email questions about it. It exists for the app you're about to hand to a client.

## Licence

MIT. See [`LICENSE.md`](LICENSE.md). Use Core for anything, commercial or not, change it, and share it; keep the copyright notice in copies of Core's source. The VanillaSaaS name and logo are not part of the licence, and VanillaSaaS Pro is licensed separately.

---

Built by Len Johnson · [vanillasaas.dev](https://vanillasaas.dev)
