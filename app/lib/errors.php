<?php
/**
 * =============================================================================
 *  app/lib/errors.php — ERRORS: LOG EVERYTHING, SHOW VISITORS NOTHING
 * =============================================================================
 *
 *  CORE FILE — replaced by VanillaSaaS Core updates. Don't edit it; put your
 *  own functions in app/custom.php so updates stay a simple file swap.
 *
 *  In production a stack trace is a gift to an attacker: it reveals file
 *  paths, SQL, library versions. So:
 *
 *    - local:      full exception details on screen (you're the only viewer)
 *    - production: a polite "something went wrong" page, details written to
 *                  storage/logs/app.log for you to read later
 *
 *  We also convert PHP warnings/notices into exceptions. "Undefined array
 *  key" silently returning null is how bugs hide; failing fast finds them.
 * =============================================================================
 */

declare(strict_types=1);

function errors_register(): void
{
    error_reporting(E_ALL);

    // Never print raw PHP errors to the page; our handler decides what shows.
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', STORAGE_PATH . '/logs/php-errors.log');

    // Warnings & notices → exceptions (unless silenced with @ on purpose).
    set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        throw new ErrorException($message, 0, $severity, $file, $line);
    });

    set_exception_handler('errors_handle_exception');

    // Fatal errors (out of memory, parse errors in included files) skip the
    // exception handler. This catches them at shutdown so they're logged too.
    register_shutdown_function(static function (): void {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            errors_handle_exception(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
        }
    });
}

/** Append a line to storage/logs/app.log. Never throws (logging must not crash the app). */
function log_message(string $level, string $message, array $context = []): void
{
    $line = sprintf(
        "[%s] %s: %s%s\n",
        gmdate('Y-m-d H:i:s'),
        strtoupper($level),
        $message,
        $context ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES) : ''
    );
    @file_put_contents(STORAGE_PATH . '/logs/app.log', $line, FILE_APPEND | LOCK_EX);
}

function errors_handle_exception(Throwable $ex): void
{
    log_message('error', get_class($ex) . ': ' . $ex->getMessage(), [
        'file' => $ex->getFile() . ':' . $ex->getLine(),
        'url'  => $_SERVER['REQUEST_URI'] ?? 'cli',
    ]);

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $ex . PHP_EOL);
        exit(1);
    }

    // Throw away any half-rendered page so the error page isn't mixed into it.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    if (headers_sent()) {
        echo is_production() ? 'An unexpected error occurred.' : e((string) $ex);
        exit(1);
    }

    try {
        view('errors/error', [
            'title'   => 'Something went wrong',
            'code'    => 500,
            'message' => 'An unexpected error occurred. It has been logged and we will look into it.',
            'debug'   => is_production() ? null : (string) $ex,
            // Error pages can appear at any address, so anchor their links.
            'baseHref' => base_href(),
        ], 'auth', 500);
    } catch (Throwable) {
        // The error page itself failed (e.g. a broken layout). Plain text it is.
        http_response_code(500);
        echo 'An unexpected error occurred.';
    }
    exit(1);
}

/**
 * Stop the request with an HTTP error page.
 *
 *   abort(404);
 *   abort(403, 'You cannot edit this project.');
 *
 * Stick to standard status codes. Apache turns codes it doesn't recognise
 * (like Laravel's 419) into a 500, which is why CSRF failures use 403.
 */
function abort(int $code, ?string $message = null, ?string $title = null): never
{
    $defaults = [
        400 => ['Bad request', 'The request could not be understood.'],
        403 => ['Forbidden', 'You do not have permission to do that.'],
        404 => ['Page not found', 'That page does not exist or has moved.'],
        405 => ['Method not allowed', 'That action is not supported here.'],
        429 => ['Too many attempts', 'Please wait a little while and try again.'],
        500 => ['Something went wrong', 'An unexpected error occurred.'],
    ];
    [$defaultTitle, $default] = $defaults[$code] ?? ['Error', 'An error occurred.'];

    view('errors/error', [
        'title'   => $title ?? $defaultTitle,
        'code'    => $code,
        'message' => $message ?? $default,
        'debug'   => null,
        // Error pages can appear at any address (/a/deep/path), where the
        // layout's relative links would break. This anchors them.
        'baseHref' => base_href(),
    ], 'auth', $code);
    exit;
}
