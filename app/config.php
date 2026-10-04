<?php
/**
 * =============================================================================
 *  app/config.php — THE ONE FILE YOU EDIT TO CONFIGURE THE APP
 * =============================================================================
 *
 *  This file returns a plain PHP array. No .env parser, no YAML, no magic:
 *  open it, change a value, save, refresh.
 *
 *  KEEPING SECRETS OUT OF GIT
 *  --------------------------
 *  Don't put production passwords in this file if you commit it. Instead,
 *  create `app/config.local.php` (already in .gitignore) that returns ONLY the
 *  keys you want to override, e.g.
 *
 *      <?php
 *      return [
 *          'app' => ['env' => 'production', 'url' => 'https://myapp.com'],
 *          'db'  => ['driver' => 'mysql', 'mysql' => ['pass' => 's3cret']],
 *      ];
 *
 *  bootstrap.php deep-merges config.local.php over this file, so you only
 *  write the values that differ. See config.local.example.php.
 *
 *  WHY THIS FILE IS SAFE FROM THE BROWSER
 *  --------------------------------------
 *  It lives in /app, which is OUTSIDE the web root (/public). Even if a
 *  visitor guesses the path, the web server never serves it. That's the
 *  single most important structural decision in this codebase.
 * =============================================================================
 */

return [

    // -------------------------------------------------------------------------
    // Application
    // -------------------------------------------------------------------------
    'app' => [
        // Shown in the browser tab, header, and outgoing emails.
        // Change this first: your users see it everywhere.
        'name' => 'Your App',

        // 'local'      → detailed error pages, emails written to a log file.
        // 'production' → generic error pages, errors logged privately,
        //                HSTS header sent over HTTPS.
        // ALWAYS set 'production' on a live server.
        'env' => 'local',

        // Your public base URL WITHOUT a trailing slash, e.g. https://myapp.com
        // Required in production: it builds password-reset links. We refuse to
        // guess it from the request's Host header in production because an
        // attacker can forge that header and make reset emails point to THEIR
        // domain ("host header injection").
        'url' => '',

        // All dates are stored in UTC and should be converted for display.
        'timezone' => 'UTC',
    ],

    // -------------------------------------------------------------------------
    // Database
    // -------------------------------------------------------------------------
    'db' => [
        // 'sqlite' → zero setup. A file is created in storage/database/ and the
        //            schema is installed automatically on the first request.
        //            Comfortably handles thousands of users on one server.
        // 'mysql'  → MySQL 5.7+/MariaDB 10.3+. Run `php bin/install.php` or
        //            import database/schema.mysql.sql in phpMyAdmin first.
        'driver' => 'sqlite',

        'sqlite' => [
            // Absolute path. Kept inside /storage, never inside /public.
            'path' => dirname(__DIR__) . '/storage/database/app.sqlite',
        ],

        'mysql' => [
            'host'    => '127.0.0.1',
            'port'    => 3306,
            'name'    => 'your_database',
            'user'    => 'root',
            'pass'    => '',
            // utf8mb4 = real UTF-8 (emoji, all scripts). Plain 'utf8' in MySQL
            // is a broken 3-byte subset. Never use it.
            'charset' => 'utf8mb4',
        ],
    ],

    // -------------------------------------------------------------------------
    // Sessions
    // -------------------------------------------------------------------------
    'session' => [
        // Cookie name. Over HTTPS we automatically prefix it with "__Host-",
        // which tells the browser: only accept this cookie if it is Secure,
        // has Path=/ and no Domain. That blocks subdomain cookie-injection.
        'name' => 'app_session',

        // Sign the user out after this many seconds of inactivity.
        'idle_timeout' => 60 * 60 * 2,        // 2 hours

        // Sign the user out this long after login, however active they are.
        'absolute_timeout' => 60 * 60 * 24,   // 24 hours
    ],

    // -------------------------------------------------------------------------
    // Security
    // -------------------------------------------------------------------------
    'security' => [
        // Set true if your site is ONLY reachable over HTTPS but PHP can't
        // detect it (rare). Forces Secure cookies and HSTS in production.
        'force_https' => false,

        // Set true ONLY if the app sits behind a reverse proxy / load balancer
        // you control (e.g. Cloudflare, a Docker proxy) that sets
        // X-Forwarded-Proto and X-Forwarded-For. If you enable this without
        // such a proxy, attackers can spoof their IP and dodge rate limits.
        'trust_proxy' => false,
    ],

    // -------------------------------------------------------------------------
    // Authentication rules
    // -------------------------------------------------------------------------
    'auth' => [
        // Length beats complexity rules. 12 is a sensible SaaS default; raise
        // it to 15 if you want to follow the strictest current NIST guidance.
        'password_min' => 12,

        // Hard upper bound in BYTES. bcrypt (the fallback algorithm on hosts
        // without Argon2) silently ignores everything after byte 72, so we
        // reject longer passwords rather than pretend they're stronger.
        'password_max_bytes' => 72,

        // Brute-force protection for the sign-in form.
        'login_max_attempts'  => 5,          // per email + IP address...
        'login_decay_seconds' => 15 * 60,    // ...within this window.
        'login_ip_max'        => 30,         // all emails, one IP, same window.

        // Password-reset links expire after this many minutes.
        'reset_expires_minutes' => 60,
    ],

    // -------------------------------------------------------------------------
    // Outgoing email
    // -------------------------------------------------------------------------
    'mail' => [
        // 'log'  → don't send; append each email to storage/logs/mail.log.
        //          Perfect for local development: open the log, click the link.
        // 'mail' → PHP's built-in mail(). Works on most cPanel/shared hosts.
        // 'custom' → your own function, app_mail_send(), in app/custom.php.
        //          Use this for a transactional email service (Postmark,
        //          Resend, Mailgun): better deliverability than mail().
        //          See the top of app/lib/mail.php for the function's shape.
        'driver'    => 'log',
        'from'      => 'no-reply@example.com',
        'from_name' => 'Your App',
    ],
];
