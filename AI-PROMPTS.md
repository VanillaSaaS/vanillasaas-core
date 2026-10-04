# AI prompts for extending VanillaSaaS Core

The codebase is small and conventional, which is exactly what AI coding assistants (Claude, ChatGPT, Cursor, Copilot) handle well. The risk is that the assistant quietly drags in a framework, writes SQL with string concatenation, or adds inline JavaScript that the Content-Security-Policy will block. Paste the context block first and those problems mostly disappear.

**How to use these:** start a new chat, paste the context block, then paste the files the prompt names (the assistant needs to see them), then the feature prompt.

---

## 1. Context block — paste this first, every time

```text
You are working on an app built on "VanillaSaaS Core", a framework-free PHP 8.1+ SaaS starter.
Follow its conventions exactly.

STRUCTURE
- public/        web root. One PHP "page controller" per URL.
- app/lib/       plain functions grouped by concern (no classes, no Composer).
- app/views/     templates: pages/, layouts/ (auth.php, app.php), partials/.
- app/custom.php my own functions. NEVER edit app/lib/ or app/bootstrap.php:
                 they are replaced by Core updates.
- database/      schema.*.sql are a frozen baseline — never edit them.
                 Database changes are migrations: one .sql file per driver in
                 database/migrations/sqlite/ AND database/migrations/mysql/,
                 same file name in both, named app-NNN-description.sql.

PAGE CONTROLLER SHAPE (copy public/dashboard.php or public/settings.php)
1. declare(strict_types=1); require __DIR__ . '/../app/bootstrap.php';
2. allow_methods('GET', 'POST');
3. $user = require_auth();   (or require_guest();)
4. if (is_post()) { csrf_verify(); read with input('field'); validate into
   $errors; on success write with db_run(), flash('success', '...'),
   redirect('page.php'); otherwise $status = 422; }
5. view('pages/name', [...data...], 'app', $status);

HELPERS AVAILABLE
config('a.b'), e($v), input($k, trim: false), query($k), redirect($to),
view(), partial(), abort($code, $msg), flash($type, $msg),
db_one($sql, $params), db_all(), db_run(), db_transaction(fn),
now_utc(), format_date(), auth_user(), auth_id(), require_auth(),
csrf_field(), csrf_verify(), throttle_key(), throttle_too_many(),
throttle_hit(), throttle_clear(), validate_email(), validate_name(),
validate_password(), password_make(), mail_send($to, $subject, $body),
base_url(), field_error($errors, 'field'), field_attrs($errors, 'field').

NON-NEGOTIABLE RULES
- SQL: always ? placeholders. Never interpolate variables into SQL.
- Every query on user-owned data includes "AND user_id = ?" with auth_id().
- Every echoed value goes through e().
- Every POST form contains <?= csrf_field() ?>; every POST handler calls csrf_verify().
- No inline <script>, no on*="" attributes, no style="" — the CSP blocks
  them. JavaScript goes in public/assets/js/, CSS in public/assets/css/app.css
  using the existing CSS variables and component classes.
- Timestamps: generated in PHP with now_utc(), stored as 'Y-m-d H:i:s' UTC.
- New user-owned tables: user_id with a foreign key ON DELETE CASCADE,
  written as a migration for BOTH drivers. Applied with: php bin/install.php
- New PHP functions go in app/custom.php (or a file it requires), never in
  app/lib/. If Core's behaviour must change, wrap it in a new function.
  Code that must run on every request goes in app_boot() in app/custom.php
  (called after the session starts). Pages in public/ are mine to edit.
- No Composer packages, no npm, no frameworks. Use PHP's standard library
  (PDO, curl, hash, random_bytes, sodium) and browser-native JS.
- Comment the code in the same teaching style as the existing files:
  explain WHY, not just what.

Reply "VanillaSaaS Core context loaded." and wait for the task.
```

---

## 2. Feature prompts

### A CRUD feature (the one you'll use most)

Paste: `public/settings.php`, `app/views/pages/dashboard.php`, `app/views/layouts/app.php`, both schema files (for reference).

```text
Add a "Projects" feature: each user can create, list, edit and delete their
own projects (title required, max 120 chars; optional description, max 2000).
- Add a projects table as migration app-001-projects.sql for BOTH drivers
  (user_id FK ON DELETE CASCADE).
- public/projects.php: list + create form. public/project-edit.php: edit and
  delete (?id=). Every query scoped to auth_id(); a project that doesn't
  belong to the user is a 404, not a 403.
- Views in app/views/pages/ using the existing .panel, .form, .field, .btn
  classes and an empty state for zero projects.
- Add "Projects" to the sidebar $nav with an icon.
Give me complete files.
```

### Email verification

Paste: `app/lib/auth.php`, `app/lib/password_reset.php`, `public/register.php`, both schema files (for reference).

```text
Add email verification modelled on password_reset.php:
- users.email_verified_at (nullable), added by a migration for both drivers.
- On register and on email change, send a link with a 256-bit token stored
  hashed (new email_verifications table, 24h expiry, single use).
- public/verify-email.php consumes the token.
- A require_verified() guard that redirects unverified users to a page
  with a "resend link" button (rate limited with throttle_*).
- Show a dismissible banner on the dashboard while unverified.
```

### "Remember me"

Paste: `app/lib/auth.php`, `app/lib/session.php`, `public/login.php`, both schema files (for reference).

```text
Add an optional "Remember me for 30 days" checkbox using the split-token
pattern: a remember_tokens table storing a selector (plain) and a SHA-256
hash of a validator. Cookie = selector:validator, HttpOnly, Secure when
HTTPS, SameSite=Lax. On a request with no session but a valid cookie,
sign the user in with auth_login() and rotate the token. Delete all of a
user's remember tokens on logout, password change and password reset.
Respect session_version.
```

### Stripe subscriptions (no SDK)

Paste: `app/lib/http.php`, `app/config.php`, `public/dashboard.php`, both schema files (for reference).

```text
Add Stripe subscriptions using only PHP curl against Stripe's REST API
(no stripe-php library):
- config: stripe.secret_key, stripe.webhook_secret, stripe.price_id.
- public/billing.php: "Subscribe" creates a Checkout Session and redirects;
  "Manage billing" opens the Customer Portal.
- public/stripe-webhook.php: verify the Stripe-Signature header manually
  (HMAC-SHA256, timestamp tolerance 5 minutes, hash_equals), handle
  checkout.session.completed and customer.subscription.updated/deleted,
  and store stripe_customer_id, subscription_status, current_period_end
  on users. This endpoint is exempt from csrf_verify() — explain why.
- A require_subscription() guard.
Idempotent webhook handling, please.
```

### Two-factor authentication (TOTP)

Paste: `app/lib/auth.php`, `public/login.php`, `public/settings.php`, both schema files (for reference).

```text
Add optional TOTP two-factor auth (RFC 6238) with no libraries:
base32 encode/decode, HMAC-SHA1 code generation, ±1 step tolerance.
- Settings: enable (show secret + otpauth:// URI as text — no external QR
  service), confirm with a code, disable with password + code.
- Store the secret encrypted with sodium_crypto_secretbox using a key from
  config, plus 8 single-use hashed recovery codes.
- Login: after a correct password, if 2FA is on, store a "pending" user id
  in the session (not signed in yet) and show a code form. Rate limit it.
- Prevent the same code being used twice within its window.
```

### Breached-password check (Have I Been Pwned)

Paste: `app/lib/validate.php`.

```text
Extend validate_password() to reject passwords found in Have I Been Pwned
using the k-anonymity range API (send only the first 5 chars of the
SHA-1 hash, compare suffixes locally). Use curl with a 3-second timeout
and the Add-Padding header. If the API is unreachable, allow the password
and log a warning — never block sign-ups on a third-party outage.
```

### Transactional email provider

Paste: `app/lib/mail.php`, `app/config.php`.

```text
In app/custom.php, write app_mail_send(string $to, string $subject,
string $body): bool for Postmark (or Resend/Mailgun — say which), using curl
and the provider's HTTP API. API key from config. Log failures with
log_message() and return false; never throw. Do not edit app/lib/mail.php:
setting mail.driver to 'custom' makes mail_send() call this function.
```

### Admin area

Paste: `app/lib/auth.php`, `app/views/layouts/app.php`, both schema files (for reference).

```text
Add users.is_admin (default 0, set manually in the database) and a
require_admin() guard that returns 404 for non-admins. Build
public/admin-users.php: paginated user list (25 per page, search by email
with a LIKE placeholder), showing created_at and last_login_at. Add the nav
item only when the signed-in user is an admin.
```

---

## 3. Review prompt — run this on anything an AI wrote

```text
Review this code against the VanillaSaaS Core rules. List every place that:
1. builds SQL with variables instead of ? placeholders
2. reads or writes user-owned rows without "AND user_id = ?"
3. echoes a value without e()
4. has a POST form without csrf_field() or a handler without csrf_verify()
5. uses inline JavaScript, on*= attributes or style="" (CSP will block)
6. adds a dependency, framework or build step
7. edits a schema file, or writes a migration for one driver but not the other
8. edits anything in app/lib/ or app/bootstrap.php instead of app/custom.php
For each, quote the line and give the fix.
```

## 4. Pull it back on track

If the assistant suggests installing a package or framework:

```text
Stop. VanillaSaaS Core has zero dependencies by design. Rewrite that using only
PHP's standard library and browser-native APIs, following the conventions
in the context block.
```
