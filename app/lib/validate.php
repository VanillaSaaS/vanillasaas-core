<?php
/**
 * =============================================================================
 *  app/lib/validate.php — INPUT VALIDATION
 * =============================================================================
 *
 *  CORE FILE — replaced by VanillaSaaS Core updates. Don't edit it; put your
 *  own functions in app/custom.php so updates stay a simple file swap.
 *
 *  Every validator returns an error message (string) or null when valid.
 *  Page controllers collect them into an $errors array keyed by field name:
 *
 *      $errors = array_filter([
 *          'email'    => validate_email($email),
 *          'password' => validate_password($password),
 *      ]);
 *      if ($errors) { ...re-show the form with messages... }
 *
 *  Validation is about DATA QUALITY. It is NOT what protects you from SQL
 *  injection (prepared statements do that) or XSS (e() does that). Keep the
 *  three ideas separate in your head.
 * =============================================================================
 */

declare(strict_types=1);

/** Count characters (not bytes): "café" is 4 characters but 5 bytes. */
function str_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : (int) preg_match_all('/./us', $value);
}

/**
 * Canonical form of an email for storage and lookup.
 * Lower-casing means Len@Example.com and len@example.com are one account.
 */
function normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function validate_email(string $email): ?string
{
    if ($email === '') {
        return 'Enter your email address.';
    }
    // 254 is the practical maximum length of an email address (RFC 5321).
    if (strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return 'Enter a valid email address, like name@example.com.';
    }
    return null;
}

function validate_name(string $name): ?string
{
    if ($name === '') {
        return 'Enter your name.';
    }
    if (str_length($name) > 100) {
        return 'Name must be 100 characters or fewer.';
    }
    // Control characters (newlines, null bytes...) have no place in a name
    // and can break emails, logs and CSV exports.
    if (preg_match('/[\p{Cc}]/u', $name)) {
        return 'Name contains characters that are not allowed.';
    }
    return null;
}

/**
 * Password rules, following modern guidance (NIST SP 800-63B):
 *   - a minimum LENGTH, no "must contain a symbol" rules (they produce
 *     "Password1!" and annoy everyone);
 *   - reject passwords on a list of the most common ones;
 *   - reject a password that's just the user's email.
 *
 * $email is optional context so we can reject "password = my email".
 */
function validate_password(string $password, string $email = ''): ?string
{
    $min = (int) config('auth.password_min', 12);
    $max = (int) config('auth.password_max_bytes', 72);

    if ($password === '') {
        return 'Enter a password.';
    }
    if (str_length($password) < $min) {
        return "Use at least {$min} characters. A short phrase of 3–4 words works well.";
    }
    if (strlen($password) > $max) {
        return "Password is too long (maximum {$max} bytes).";
    }
    if (str_contains($password, "\0")) {
        return 'Password contains characters that are not allowed.';
    }
    if (password_is_common($password)) {
        return 'That password is too common. Choose something less predictable.';
    }
    if ($email !== '' && strtolower($password) === strtolower($email)) {
        return 'Your password cannot be your email address.';
    }
    return null;
}

/**
 * A short list of the most-used passwords that meet a 12-character minimum.
 * For a bigger list, load a file of the top 100k passwords here, or call the
 * free "Have I Been Pwned" range API (k-anonymity: the full password never
 * leaves your server). See AI-PROMPTS.md for a ready-made prompt.
 */
function password_is_common(string $password): bool
{
    static $common = [
        '123456789012', '1234567890123', '12345678910', '111111111111', '000000000000',
        'password1234', 'password12345', 'passwordpassword', 'password123!', 'qwertyuiop12',
        'qwertyuiopas', 'qwerty123456', '1q2w3e4r5t6y', '1qaz2wsx3edc', 'iloveyou1234',
        'abc123456789', 'abcdefghijkl', 'letmein12345', 'welcome12345', 'welcome123!',
        'administrator', 'admin1234567', 'football1234', 'baseball1234', 'superman1234',
        'princess1234', 'sunshine1234', 'trustno11234', 'changeme1234', 'monkey123456',
        'dragon123456', 'master123456', 'starwars1234', 'whatever1234', 'passw0rd1234',
        'p@ssw0rd1234', 'p@ssword1234', 'qwertyqwerty', 'asdfghjkl123', 'zxcvbnm12345',
        'correcthorsebatterystaple', 'mypassword123', 'secret123456', 'computer1234',
    ];
    return in_array(strtolower($password), $common, true);
}
