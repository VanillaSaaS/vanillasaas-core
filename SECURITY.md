# Security model

What each defence stops, where it lives in the code, and — just as important — what VanillaSaaS Core does **not** protect against. If you're extending the app, read the last section.

## Defences

| Threat | Defence | Where |
|---|---|---|
| Downloading the database, config or logs | Only `/public` is web-reachable. `app/`, `storage/`, `database/`, `bin/` sit outside it, each with a `Require all denied` `.htaccess` as a second layer. | Folder layout, `.htaccess` files |
| SQL injection | Every query uses PDO prepared statements with `?` placeholders; emulated prepares are off. | `app/lib/db.php` |
| Cross-site scripting (XSS) | All output escaped with `e()`. A strict Content-Security-Policy blocks inline scripts, so a missed escape still can't execute code. | `helpers.php`, `http.php` |
| Cross-site request forgery (CSRF) | 256-bit per-session token on every POST form, compared in constant time; rotated at sign-in. `SameSite=Lax` cookies add a second layer. | `app/lib/csrf.php` |
| Password cracking after a breach | Argon2id (bcrypt where Argon2 isn't compiled in). Hashes are upgraded automatically on next sign-in. | `app/lib/auth.php` |
| Online password guessing | 5 failures per email+IP and 30 per IP per 15 minutes. Counters live in the database, so discarding cookies doesn't reset them. | `throttle.php`, `public/login.php` |
| Account lockout as an attack | Limits are keyed on email **+ IP**, so an attacker can't lock a real user out from elsewhere. | `public/login.php` |
| Finding which emails are registered | Same message for wrong password and unknown email; unknown-email logins verify against a dummy hash so timing matches; forgot-password always gives the same reply. | `auth.php`, `password_reset.php` |
| Session hijacking | `HttpOnly` cookie (unreadable by JavaScript), `Secure` + `__Host-` prefix over HTTPS, strict mode, IDs never in URLs, session files in `storage/sessions` rather than shared `/tmp`. | `app/lib/session.php` |
| Session fixation | New session ID at sign-in, sign-out and password change. | `auth.php` |
| Stolen session living forever | Server-enforced 2-hour idle and 24-hour absolute timeouts. | `session.php` |
| Compromised password still signed in elsewhere | `session_version` bumped on password change/reset; every older session is rejected on its next request. | `auth.php`, `settings.php` |
| Takeover from an unlocked laptop | Changing email or password, or deleting the account, requires the current password (rate-limited). | `public/settings.php` |
| Reset-link theft | Tokens are 256-bit random, stored only as SHA-256 hashes, expire in 60 minutes, single use, invalidated by a newer request or an email change. Reset page sends `Referrer-Policy: no-referrer`. | `password_reset.php` |
| Poisoned reset links (host header injection) | Production refuses to build links from the request's Host header; it requires `app.url`. | `http.php` → `base_url()` |
| Email header injection | Recipient validated as a single address; newlines stripped from subject and sender name. | `app/lib/mail.php` |
| Open redirect after sign-in | "Return to" paths must be local (`/x`, never `//evil.com` or `https://…`). | `helpers.php` → `safe_local_path()` |
| Clickjacking | `frame-ancestors 'none'` and `X-Frame-Options: DENY`. | `http.php` |
| Logout CSRF | Sign-out is POST-only with a CSRF token. | `public/logout.php` |
| Leaking internals on errors | Production shows a generic page; details go to `storage/logs/app.log`. | `app/lib/errors.php` |
| Signed-in pages cached on shared computers | `Cache-Control: no-store` on every page behind `require_auth()`. | `auth.php` |
| Duplicate accounts by letter case | Emails lower-cased in PHP and unique case-insensitively in both databases. | `validate.php`, schema files |

## Known limits

These are trade-offs, not oversights. Change them if your product needs to.

- **Sign-up reveals whether an email is registered.** "An account with this email already exists" is friendlier than the alternative. The per-IP sign-up limit stops bulk harvesting. For privacy-sensitive products, show a neutral message and email the existing account holder instead (see `public/register.php`).
- **No email verification.** Anyone can register with an address they don't own. They can't take over that address's account, but you can't trust an unverified email for billing or notifications. See `AI-PROMPTS.md`.
- **No two-factor authentication.**
- **Rate limits use fixed windows and the database.** Good for one server. A distributed attack from many IPs against one account is slowed (5 per IP) but not stopped; add a per-email cap or CAPTCHA if you see that in your logs.
- **`mail()` deliverability depends on your host.** Use a transactional email provider for anything customer-facing (`mail.driver` = `custom`).
- **`trust_proxy` is all-or-nothing.** Only enable it when a proxy you control sits in front of the app; otherwise clients can spoof their IP.
- **The common-password list is short.** For a stronger check, use the Have I Been Pwned range API (prompt in `AI-PROMPTS.md`).
- **SQLite is single-server.** For multiple app servers, use MySQL.

## When you add features

- Scope every query to the signed-in user: `WHERE id = ? AND user_id = ?`. Missing this is the most common SaaS data leak.
- New forms: `csrf_field()` in the form, `csrf_verify()` in the handler.
- New output: `e()` every value. No inline JavaScript or `style=""` (the CSP will block them, which is the point).
- File uploads: store outside `/public`, generate your own file names, check type by content, cap the size.
- Anything sensitive (changing billing, deleting data): ask for the current password, as `settings.php` does.

## Reporting a vulnerability

Found a flaw in VanillaSaaS Core itself? Email security@vanillasaas.dev with the steps to reproduce it, rather than opening a public issue, so a fix can be released before the details are.

If you ship an app built on Core, replace this section with your own contact address.
