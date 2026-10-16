<?php
declare(strict_types=1);

final class Security
{
    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        if (config('trust_proxy') && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
            return true;
        }
        return false;
    }

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '6');
        $sessionDir = STORAGE_PATH . '/sessions';
        if (is_dir($sessionDir) && is_writable($sessionDir)) {
            session_save_path($sessionDir);
        }
        session_name('avs_sid');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => url('/'),
            'secure' => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    /** Security headers for every response. No inline scripts or styles are used, so the CSP stays strict. */
    public static function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        $script = ["'self'"];
        $connect = ["'self'"];
        $img = ["'self'", 'data:'];
        $frame = [];
        $google = setting('ga_measurement_id') !== '' || setting('google_ads_id') !== '';
        if ($google) {
            $script[] = 'https://www.googletagmanager.com';
            array_push($connect, 'https://*.google-analytics.com', 'https://*.analytics.google.com', 'https://*.googletagmanager.com');
            array_push($img, 'https://*.google-analytics.com', 'https://*.googletagmanager.com');
        }
        if (setting('google_ads_id') !== '') {
            array_push($script, 'https://www.googleadservices.com', 'https://googleads.g.doubleclick.net', 'https://www.google.com');
            array_push($connect, 'https://www.googleadservices.com', 'https://googleads.g.doubleclick.net', 'https://www.google.com', 'https://*.doubleclick.net');
            array_push($img, 'https://www.googleadservices.com', 'https://googleads.g.doubleclick.net', 'https://www.google.com', 'https://*.doubleclick.net', 'https://*.google.com');
            array_push($frame, 'https://*.doubleclick.net', 'https://www.googletagmanager.com');
        }
        if (setting('meta_pixel_id') !== '') {
            $script[] = 'https://connect.facebook.net';
            array_push($connect, 'https://www.facebook.com', 'https://connect.facebook.net');
            $img[] = 'https://www.facebook.com';
        }
        $csp = implode('; ', [
            "default-src 'self'",
            'script-src ' . implode(' ', $script),
            "style-src 'self'",
            'img-src ' . implode(' ', $img),
            "font-src 'self'",
            'connect-src ' . implode(' ', $connect),
            'frame-src ' . ($frame ? implode(' ', $frame) : "'none'"),
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]) . (self::isHttps() ? '; upgrade-insecure-requests' : '');
        header('Content-Security-Policy: ' . $csp);
        if (setting('search_indexing', '1') !== '1') {
            header('X-Robots-Tag: noindex, nofollow');
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('X-Permitted-Cross-Domain-Policies: none');
        if (self::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        header_remove('X-Powered-By');
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        return is_string($token) && !empty($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
    }

    public static function clientIp(): string
    {
        if (config('trust_proxy')) {
            foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR'] as $header) {
                if (!empty($_SERVER[$header])) {
                    $ip = trim(explode(',', $_SERVER[$header])[0]);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    /** Visitor IPs are stored only as keyed hashes, never in plain text. */
    public static function ipHash(?string $ip = null): string
    {
        return hash_hmac('sha256', $ip ?? self::clientIp(), (string) config('app_key'));
    }

    /**
     * Sliding-window rate limit. Returns false when the limit is reached.
     * Each allowed call is recorded, so call it once per real attempt.
     */
    public static function rateLimit(string $bucket, int $max, int $windowSeconds, ?string $subject = null): bool
    {
        $subject = $subject ?? self::ipHash();
        $since = gmdate('Y-m-d H:i:s', time() - $windowSeconds);
        $count = (int) Database::value(
            'SELECT COUNT(*) FROM rate_limits WHERE bucket = ? AND subject = ? AND created_at > ?',
            [$bucket, $subject, $since]
        );
        if ($count >= $max) {
            return false;
        }
        Database::run('INSERT INTO rate_limits (bucket, subject) VALUES (?, ?)', [$bucket, $subject]);
        if (random_int(1, 50) === 1) {
            Database::run('DELETE FROM rate_limits WHERE created_at < ?', [gmdate('Y-m-d H:i:s', time() - 86400)]);
        }
        return true;
    }

    public static function rateCount(string $bucket, string $subject, int $windowSeconds): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM rate_limits WHERE bucket = ? AND subject = ? AND created_at > ?',
            [$bucket, $subject, gmdate('Y-m-d H:i:s', time() - $windowSeconds)]
        );
    }

    public static function clearRate(string $bucket, string $subject): void
    {
        Database::run('DELETE FROM rate_limits WHERE bucket = ? AND subject = ?', [$bucket, $subject]);
    }

    /** Signed render time, used to reject forms submitted faster than a human could. */
    public static function formStamp(): string
    {
        $t = (string) time();
        return $t . '.' . substr(hash_hmac('sha256', $t, (string) config('app_key')), 0, 24);
    }

    /** True when the honeypot is empty and the form took a believable amount of time. */
    public static function looksHuman(array $input, int $minSeconds = 3): bool
    {
        if (!empty($input['website'])) {
            return false;
        }
        $stamp = (string) ($input['_ts'] ?? '');
        if (!preg_match('/^(\d{10})\.([a-f0-9]{24})$/', $stamp, $m)) {
            return false;
        }
        $expected = substr(hash_hmac('sha256', $m[1], (string) config('app_key')), 0, 24);
        if (!hash_equals($expected, $m[2])) {
            return false;
        }
        $age = time() - (int) $m[1];
        return $age >= $minSeconds && $age <= 86400;
    }
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Security::csrfToken()) . '">';
}

/** Hidden anti-spam fields: a honeypot that people never see and a signed timestamp. */
function bot_fields(): string
{
    return '<div class="hp" aria-hidden="true"><label>Leave this empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>'
        . '<input type="hidden" name="_ts" value="' . e(Security::formStamp()) . '">';
}
