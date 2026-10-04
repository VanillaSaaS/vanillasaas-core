<?php
/**
 * =============================================================================
 *  app/config.local.example.php — TEMPLATE FOR YOUR PRIVATE OVERRIDES
 * =============================================================================
 *
 *  1. Copy this file to `app/config.local.php`.
 *  2. Keep only the keys you need to change.
 *  3. Never commit config.local.php (it's in .gitignore).
 *
 *  Values here are deep-merged over app/config.php, so nested keys you leave
 *  out keep their defaults.
 * =============================================================================
 */

return [
    'app' => [
        'env' => 'production',
        'url' => 'https://your-domain.com',
    ],

    'db' => [
        'driver' => 'mysql',
        'mysql'  => [
            'host' => 'localhost',
            'name' => 'your_database',
            'user' => 'your_db_user',
            'pass' => 'a-long-random-password',
        ],
    ],

    'mail' => [
        'driver'    => 'mail',
        'from'      => 'no-reply@your-domain.com',
        'from_name' => 'Your App',
    ],
];
