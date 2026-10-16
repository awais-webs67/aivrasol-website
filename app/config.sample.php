<?php
/**
 * Copy this file to app/config.php and fill in your real values.
 * Never commit app/config.php to a public repository.
 */
return [
    // 'production' hides errors from visitors. Use 'development' only on your own computer.
    'env' => 'production',

    // Full public address of the site, without a trailing slash.
    'base_url' => 'https://www.aivrasol.com',

    'db' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'name'     => 'aivrasol',
        'user'     => 'aivrasol_user',
        'password' => 'change-me',
    ],

    // Long random string. Generate one with: php -r "echo bin2hex(random_bytes(32));"
    // Used to hash visitor IPs and sign form timestamps. Changing it logs everyone out.
    'app_key' => 'replace-with-64-random-hex-characters',

    // One-time token that unlocks /admin/setup to create the first admin account.
    // After the first admin exists, the setup page is disabled automatically.
    'setup_token' => 'replace-with-a-long-random-token',

    // Email alerts for new leads. Leave 'host' empty to use PHP mail().
    'mail' => [
        'from_email' => 'no-reply@aivrasol.com',
        'from_name'  => 'AivraSol Website',
        'host'       => '',            // e.g. smtp.gmail.com or mail.yourhost.com
        'port'       => 587,           // 587 for STARTTLS, 465 for SSL
        'encryption' => 'tls',         // 'tls', 'ssl' or ''
        'username'   => '',
        'password'   => '',
    ],

    // Admin session idle timeout in minutes.
    'admin_idle_minutes' => 30,

    // Set true only when the site sits behind a trusted proxy or CDN (Cloudflare etc.)
    // so the real visitor IP is read from the proxy header.
    'trust_proxy' => false,
];
