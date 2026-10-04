<?php
/**
 * =============================================================================
 *  app/lib/http.php — HTTPS DETECTION, CLIENT IP, SECURITY HEADERS
 * =============================================================================
 *
 *  CORE FILE — replaced by VanillaSaaS Core updates. Don't edit it; put your
 *  own functions in app/custom.php so updates stay a simple file swap.
 */

declare(strict_types=1);

/**
 * Is this request encrypted?
 *
 * Decides whether cookies get the Secure flag and whether we send HSTS.
 * Behind a proxy (Cloudflare, a load balancer) PHP sees plain HTTP from the
 * proxy, so we trust X-Forwarded-Proto — but ONLY when you've said a proxy
 * exists (security.trust_proxy). Otherwise anyone could send that header.
 */
function http_is_https(): bool
{
    if (config('security.force_https')) {
        return true;
    }
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
        return true;
    }
    if (config('security.trust_proxy')) {
        return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }
    return false;
}

/**
 * The visitor's IP address, used as part of rate-limit keys.
 *
 * REMOTE_ADDR is the address that actually connected to the server and
 * can't be forged. X-Forwarded-For is just a header — trivially faked — so
 * we only read it when trust_proxy is on, and take the left-most entry
 * (the original client as reported by your proxy).
 */
function client_ip(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

    if (config('security.trust_proxy') && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwarded = trim(explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        if (filter_var($forwarded, FILTER_VALIDATE_IP)) {
            $ip = $forwarded;
        }
    }
    return $ip;
}

/**
 * Absolute base URL of the app, used ONLY for links inside emails.
 *
 * Production: must come from config('app.url'). We refuse to build it from
 * the Host header, because an attacker can request "forgot password" for
 * your user while sending `Host: evil.com` — and the emailed reset link
 * would point to evil.com, handing them the token.
 *
 * Local: we derive it from the request so you need zero setup to test.
 */
function base_url(): string
{
    $configured = rtrim((string) config('app.url', ''), '/');
    if ($configured !== '') {
        return $configured;
    }
    if (is_production()) {
        throw new RuntimeException("Set 'app.url' in your config before running in production.");
    }

    $scheme = http_is_https() ? 'https' : 'http';
    $host   = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $path   = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $dir    = rtrim(str_replace('\\', '/', dirname($path)), '/');

    return "{$scheme}://{$host}{$dir}";
}

/**
 * Security headers sent with every page. Each is a cheap, browser-enforced
 * layer of defence. See SECURITY.md for the long version.
 */
function http_send_security_headers(): void
{
    // Content-Security-Policy: the browser will only run scripts/styles that
    // come from our own domain. Even if an attacker sneaks
    // <script>steal()</script> into a page, the browser refuses to run it,
    // because inline scripts aren't allowed. That's why this codebase has
    // ZERO inline <script> or style="" attributes — keep it that way.
    header(
        "Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; "
        . "img-src 'self' data:; font-src 'self'; connect-src 'self'; form-action 'self'; "
        . "frame-ancestors 'none'; base-uri 'self'; object-src 'none'"
    );

    // Don't let the browser guess ("sniff") content types — stops a text file
    // being executed as a script.
    header('X-Content-Type-Options: nosniff');

    // Nobody may put our pages in an <iframe> (stops clickjacking). The CSP
    // frame-ancestors above does the same in modern browsers; this covers old ones.
    header('X-Frame-Options: DENY');

    // Send only our origin (not full URLs with tokens) to other sites.
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // We don't use these device features, so nobody embedded in us can either.
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');

    // Isolate our window from pages that open us (blocks some cross-window attacks).
    header('Cross-Origin-Opener-Policy: same-origin');

    // HSTS: "only ever talk to me over HTTPS for the next year". Only sent in
    // production over HTTPS — sending it on localhost can lock your browser
    // out of http://localhost for a year.
    if (is_production() && http_is_https()) {
        header('Strict-Transport-Security: max-age=31536000');
    }

    // Don't advertise the PHP version (where the server allows removing it).
    header_remove('X-Powered-By');
}

/**
 * Tell browsers and proxies never to store this page. Used on every
 * signed-in page so that after logging out, the Back button can't show a
 * cached copy of the dashboard on a shared computer.
 */
function http_no_store(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    header('Pragma: no-cache');
}
