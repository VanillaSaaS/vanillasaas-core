<?php
/**
 * =============================================================================
 *  app/lib/mail.php — OUTGOING EMAIL
 * =============================================================================
 *
 *  CORE FILE — replaced by VanillaSaaS Core updates. Don't edit it; put your
 *  own functions in app/custom.php so updates stay a simple file swap.
 *
 *  mail_send($to, $subject, $body) is the only function the rest of the app
 *  calls.
 *
 *  Drivers (config: mail.driver):
 *    'log'    → write the email to storage/logs/mail.log (development)
 *    'mail'   → PHP's mail() function (most shared/cPanel hosts)
 *    'custom' → hand the email to YOUR function, app_mail_send(), which you
 *               write in app/custom.php. This is how you plug in a
 *               transactional email service (Postmark, Resend, Mailgun,
 *               Amazon SES) without editing this file:
 *
 *                   function app_mail_send(string $to, string $subject, string $body): bool
 *                   {
 *                       // call your provider's HTTP API with curl;
 *                       // return true if it accepted the message
 *                   }
 *
 *               $to and $subject have already been checked for header
 *               injection by the time your function is called.
 * =============================================================================
 */

declare(strict_types=1);

/**
 * Send a plain-text email. Returns true if it was handed off successfully.
 */
function mail_send(string $to, string $subject, string $body): bool
{
    // HEADER INJECTION GUARD: a newline in a header lets an attacker add their
    // own headers (e.g. "Bcc: 10,000 people"). The recipient must be a valid
    // single address and the subject must be one line.
    if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
        log_message('warning', 'mail_send: invalid recipient refused');
        return false;
    }
    $subject  = str_replace(["\r", "\n"], ' ', $subject);
    $from     = (string) config('mail.from');
    $fromName = str_replace(["\r", "\n", '"'], '', (string) config('mail.from_name', config('app.name')));

    if (config('mail.driver') === 'log') {
        $entry = sprintf(
            "==== %s UTC ====\nTo: %s\nFrom: %s <%s>\nSubject: %s\n\n%s\n\n",
            gmdate('Y-m-d H:i:s'), $to, $fromName, $from, $subject, $body
        );
        return file_put_contents(STORAGE_PATH . '/logs/mail.log', $entry, FILE_APPEND | LOCK_EX) !== false;
    }

    if (config('mail.driver') === 'custom') {
        // Your own sender, defined in app/custom.php (see the top of this file).
        if (!function_exists('app_mail_send')) {
            log_message('error', "mail.driver is 'custom' but app_mail_send() is not defined in app/custom.php");
            return false;
        }
        return app_mail_send($to, $subject, $body);
    }

    $headers = [
        'From'                      => sprintf('"%s" <%s>', $fromName, $from),
        'Reply-To'                  => $from,
        'MIME-Version'              => '1.0',
        'Content-Type'              => 'text/plain; charset=UTF-8',
        'Content-Transfer-Encoding' => '8bit',
    ];

    // Encode the subject so non-ASCII characters (é, ñ, emoji) survive.
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    // "-f" sets the envelope sender, which improves deliverability on many
    // hosts. Only passed when it's a valid address (it goes to sendmail).
    $params = filter_var($from, FILTER_VALIDATE_EMAIL) ? '-f' . $from : '';

    $sent = mail($to, $encodedSubject, $body, $headers, $params);
    if (!$sent) {
        log_message('error', 'mail() returned false', ['subject' => $subject]);
    }
    return $sent;
}
