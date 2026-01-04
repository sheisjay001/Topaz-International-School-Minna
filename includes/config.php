<?php
// Database Configuration
// ideally this file should be outside the web root or protected
// But for XAMPP structure, we keep it here.

// TiDB Credentials
define('DB_HOST', getenv('DB_HOST') ?: 'gateway01.eu-central-1.prod.aws.tidbcloud.com');
define('DB_USER', getenv('DB_USER') ?: '2BkB857kEgJJLLq.root');
define('DB_PASS', getenv('DB_PASS') ?: 'OgauaDn0XUAZRuQ0');
define('DB_NAME', getenv('DB_NAME') ?: 'test');
define('DB_PORT', getenv('DB_PORT') ?: 4000);

// App Settings
define('APP_NAME', 'Topaz International School');
define('APP_URL', 'http://localhost/Topaz international school minna');
define('IS_DEV', true); // Set to false in production

// Security Settings
define('SESSION_LIFETIME', 3600); // 1 hour

// SMTP Settings (use environment variables in production)
// Load from .env if available (Local Development)
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && substr($line, 0, 1) !== '#') {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp-relay.brevo.com');
define('SMTP_USER', getenv('SMTP_USER') ?: '9ea7ad001@smtp-brevo.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: ''); // Set via environment variable in production
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'autajoy2003@gmail.com');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Topaz International School');
