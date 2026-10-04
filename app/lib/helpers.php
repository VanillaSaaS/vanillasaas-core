<?php
/**
 * =============================================================================
 *  app/lib/helpers.php — SMALL FUNCTIONS USED EVERYWHERE
 * =============================================================================
 *
 *  CORE FILE — replaced by VanillaSaaS Core updates. Don't edit it; put your
 *  own functions in app/custom.php so updates stay a simple file swap.
 *
 *  config()   read a setting, e.g. config('app.name')
 *  e()        escape text for HTML output (your #1 defence against XSS)
 *  redirect() send the browser elsewhere and stop
 *  view()     render a template inside a layout
 *  input()    read a POSTed field safely as a string
 * =============================================================================
 */

declare(strict_types=1);

// -----------------------------------------------------------------------------
// Configuration
// -----------------------------------------------------------------------------

/**
 * Load config.php, then deep-merge config.local.php over it.
 *
 * Why a function with a `static` variable instead of a global $config?
 * Globals can be overwritten by any file by accident. A static inside a
 * function is private to that function: the only way in is config_load(),
 * the only way out is config().
 */
function config_load(string $basePath, string $localPath): void
{
    $config = require $basePath;

    if (is_file($localPath)) {
        $config = array_replace_recursive($config, require $localPath);
    }

    config_store($config);
}

/** @internal Holds the loaded configuration. */
function config_store(?array $set = null): array
{
    static $config = [];
    if ($set !== null) {
        $config = $set;
    }
    return $config;
}

/**
 * Read a setting using "dot notation": config('db.mysql.host').
 * Returns $default if any part of the path is missing.
 */
function config(string $key, mixed $default = null): mixed
{
    $value = config_store();
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function is_production(): bool
{
    return config('app.env') === 'production';
}

// -----------------------------------------------------------------------------
// Output escaping
// -----------------------------------------------------------------------------

/**
 * Escape a value for safe output inside HTML text or a quoted attribute.
 *
 * RULE: every variable you echo into a template goes through e(). No
 * exceptions, even values "from our own database" — a user's name is
 * user input, and `<script>` is a perfectly valid thing to type into a form.
 *
 * ENT_QUOTES escapes both ' and ", so e() is safe inside attribute="..." and
 * attribute='...'. ENT_SUBSTITUTE replaces invalid UTF-8 rather than
 * returning an empty string (which could silently hide data).
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// -----------------------------------------------------------------------------
// Requests
// -----------------------------------------------------------------------------

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/**
 * Read a POST field as a trimmed string.
 *
 * Why not just $_POST['email']? Because an attacker can send
 * `email[]=x`, making it an ARRAY, and string functions would then throw.
 * Anything that isn't a plain string becomes ''. Passwords should NOT be
 * trimmed (a trailing space is a legitimate character), hence $trim.
 */
function input(string $key, bool $trim = true): string
{
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) {
        return '';
    }
    return $trim ? trim($value) : $value;
}

/** Same as input(), for the query string ($_GET). */
function query(string $key): string
{
    $value = $_GET[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Only allow these HTTP methods on this page; anything else gets a 405. */
function allow_methods(string ...$methods): void
{
    // HEAD is "GET without the body" (uptime monitors and link checkers use
    // it), so wherever GET is allowed, HEAD is too.
    if (in_array('GET', $methods, true)) {
        $methods[] = 'HEAD';
    }
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, $methods, true)) {
        header('Allow: ' . implode(', ', $methods));
        abort(405);
    }
}

/**
 * The folder the app is served from: '' at https://myapp.com/, or '/my-app'
 * at http://localhost/my-app/.
 *
 * Pages normally don't need this, because every link is relative
 * ("dashboard.php", "assets/css/app.css") and all pages sit side by side.
 * Error pages are the exception: a 404 can be shown for ANY address, such as
 * /some/deep/path, where a relative link would point at the wrong place.
 * They use this to anchor their links (see base_href() and the layouts).
 *
 * HOW: PHP knows where the running script is (SCRIPT_NAME). When the root
 * .htaccess has quietly routed the request into /public, that path ends in
 * "/public" but the address the visitor typed doesn't contain it, so we
 * drop it.
 */
function base_path(): string
{
    $scriptDir = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
    $requested = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

    $inside = static fn (string $dir): bool => $dir === '' || $requested === $dir || str_starts_with($requested, $dir . '/');

    if (!$inside($scriptDir) && str_ends_with($scriptDir, '/public')) {
        $scriptDir = substr($scriptDir, 0, -strlen('/public'));
    }
    return $inside($scriptDir) ? $scriptDir : '';
}

/** base_path() with a trailing slash, ready for <base href="...">. */
function base_href(): string
{
    return base_path() . '/';
}

/**
 * The address being asked for, relative to the app: "" for the home page,
 * "dashboard.php", "some/unknown/path".
 */
function request_path(): string
{
    $requested = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $base      = base_path();
    if ($base !== '' && str_starts_with($requested, $base)) {
        $requested = substr($requested, strlen($base));
    }
    return trim(rawurldecode($requested), '/');
}

// -----------------------------------------------------------------------------
// Responses
// -----------------------------------------------------------------------------

/**
 * Redirect and stop executing.
 *
 * We redirect to RELATIVE paths like 'dashboard.php'. The browser resolves
 * them against the current URL, so the app works unchanged at
 * https://myapp.com/ or at http://localhost/my-folder/ — no base-URL config.
 *
 * 303 "See Other" is the correct status after a form POST: it tells the
 * browser to follow up with a GET, so pressing Refresh never re-submits.
 * This is the Post/Redirect/Get pattern.
 */
function redirect(string $to, int $status = 303): never
{
    header('Location: ' . $to, true, $status);
    exit;
}

/**
 * Accept only same-site paths for "send me back where I was" redirects.
 *
 * Without this check, a link like login.php?next=https://evil.com would
 * bounce users to a phishing page right after they sign in ("open redirect").
 * We allow '/something' but reject '//evil.com' (protocol-relative URL)
 * and backslash tricks some browsers treat as slashes.
 */
function safe_local_path(?string $path, string $fallback): string
{
    if ($path === null || $path === '' || $path[0] !== '/'
        || str_starts_with($path, '//') || str_contains($path, '\\')
        || preg_match('/[\x00-\x1F]/', $path)) {
        return $fallback;
    }
    return $path;
}

/**
 * Render a template from app/views inside a layout.
 *
 *   view('pages/login', ['title' => 'Sign in'], 'auth');
 *
 * renders app/views/pages/login.php, captures its output as $content, then
 * renders app/views/layouts/auth.php which prints $content where it wants.
 *
 * Data keys become variables in the template: ['title' => 'x'] → $title.
 */
function view(string $template, array $data = [], ?string $layout = 'app', int $status = 200): void
{
    http_response_code($status);

    $content = render_file(VIEW_PATH . "/{$template}.php", $data);

    if ($layout === null) {
        echo $content;
        return;
    }

    echo render_file(VIEW_PATH . "/layouts/{$layout}.php", $data + ['content' => $content]);
}

/**
 * Include a PHP template in an isolated scope and return its output.
 *
 * The leading underscores on $__file/$__data avoid clashing with your
 * template variables. EXTR_SKIP means a data key can never overwrite them.
 */
function render_file(string $__file, array $__data): string
{
    if (!is_file($__file)) {
        throw new RuntimeException("View not found: {$__file}");
    }
    extract($__data, EXTR_SKIP);
    ob_start();
    try {
        require $__file;
        return (string) ob_get_clean();
    } catch (Throwable $ex) {
        ob_end_clean();
        throw $ex;
    }
}

/** Render a small partial (no layout) and return it — handy inside views. */
function partial(string $name, array $data = []): string
{
    return render_file(VIEW_PATH . "/partials/{$name}.php", $data);
}

// -----------------------------------------------------------------------------
// Form helpers for templates
// -----------------------------------------------------------------------------

/**
 * The error message under a field, or '' if that field is fine.
 * The id lets the input point at it with aria-describedby, so screen readers
 * announce the error when the field gets focus.
 */
function field_error(array $errors, string $field): string
{
    if (empty($errors[$field])) {
        return '';
    }
    return '<p class="field-error" id="' . e($field) . '-error">' . e($errors[$field]) . '</p>';
}

/** Accessibility attributes for an input that may have an error. */
function field_attrs(array $errors, string $field): string
{
    return empty($errors[$field])
        ? ''
        : ' aria-invalid="true" aria-describedby="' . e($field) . '-error"';
}

/** First letter of a name, upper-cased, multibyte-safe ("élodie" → "É"). */
function initial(string $name): string
{
    if (preg_match('/^\X/u', trim($name), $m)) {
        return function_exists('mb_strtoupper') ? mb_strtoupper($m[0], 'UTF-8') : strtoupper($m[0]);
    }
    return '?';
}

// -----------------------------------------------------------------------------
// Time
// -----------------------------------------------------------------------------

/**
 * Current UTC time as 'Y-m-d H:i:s'.
 *
 * We generate timestamps in PHP, not with SQL's NOW(), because SQLite and
 * MySQL disagree on time functions. One source of truth = identical
 * behaviour on both databases.
 */
function now_utc(): string
{
    return gmdate('Y-m-d H:i:s');
}

/** Format a stored UTC timestamp for display, e.g. "3 Oct 2026". */
function format_date(?string $utc, string $format = 'j M Y'): string
{
    if (!$utc) {
        return '—';
    }
    $date = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
    return $date->setTimezone(new DateTimeZone(date_default_timezone_get()))->format($format);
}
