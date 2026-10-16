<?php
declare(strict_types=1);

final class Auth
{
    private const MAX_FAILS = 5;
    private const LOCK_SECONDS = 900;

    public static function user(): ?array
    {
        static $user = false;
        if ($user !== false) {
            return $user;
        }
        $user = null;
        $id = (int) ($_SESSION['admin_id'] ?? 0);
        if ($id <= 0) {
            return null;
        }
        $idle = (int) config('admin_idle_minutes', 30) * 60;
        $fingerprint = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . config('app_key'));
        if (time() - (int) ($_SESSION['admin_seen'] ?? 0) > $idle || !hash_equals((string) ($_SESSION['admin_fp'] ?? ''), $fingerprint)) {
            self::logout();
            flash('error', 'Your session expired. Please sign in again.');
            return null;
        }
        $_SESSION['admin_seen'] = time();
        $row = Database::one('SELECT id, name, email, role FROM users WHERE id = ? AND is_active = 1', [$id]);
        $user = $row;
        return $user;
    }

    public static function require(?string $role = null): array
    {
        $user = self::user();
        if (!$user) {
            redirect(url('admin/login'));
        }
        if ($role !== null && $user['role'] !== $role) {
            abort(403);
        }
        return $user;
    }

    /** Returns null on success or an error message for the login form. */
    public static function attempt(string $email, string $password): ?string
    {
        $ipKey = Security::ipHash();
        $emailKey = hash_hmac('sha256', strtolower($email), (string) config('app_key'));
        if (Security::rateCount('login_ip', $ipKey, self::LOCK_SECONDS) >= self::MAX_FAILS * 3
            || Security::rateCount('login_email', $emailKey, self::LOCK_SECONDS) >= self::MAX_FAILS) {
            return 'Too many failed attempts. Please wait 15 minutes and try again.';
        }

        $user = Database::one('SELECT id, password_hash, is_active FROM users WHERE email = ?', [strtolower($email)]);
        // Verify against a dummy hash when the user does not exist so timing does not reveal valid emails.
        $hash = $user['password_hash'] ?? '$2y$12$moBWVc5xSqbObWQfcBmx..UZSZ23F3T3LzPcm64UEbgK57W3w8fj.';
        $valid = password_verify($password, $hash) && $user && (int) $user['is_active'] === 1;

        if (!$valid) {
            Security::rateLimit('login_ip', PHP_INT_MAX, self::LOCK_SECONDS, $ipKey);
            Security::rateLimit('login_email', PHP_INT_MAX, self::LOCK_SECONDS, $emailKey);
            self::audit(null, 'login_failed', $email);
            return 'Email or password is incorrect.';
        }

        if (password_needs_rehash($user['password_hash'], self::algo())) {
            Database::run('UPDATE users SET password_hash = ? WHERE id = ?', [self::hash($password), $user['id']]);
        }
        Security::clearRate('login_email', $emailKey);
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $user['id'];
        $_SESSION['admin_seen'] = time();
        $_SESSION['admin_fp'] = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . config('app_key'));
        unset($_SESSION['_csrf']);
        Database::run('UPDATE users SET last_login_at = UTC_TIMESTAMP() WHERE id = ?', [$user['id']]);
        self::audit((int) $user['id'], 'login', $email);
        return null;
    }

    public static function logout(): void
    {
        unset($_SESSION['admin_id'], $_SESSION['admin_seen'], $_SESSION['admin_fp'], $_SESSION['_csrf']);
        session_regenerate_id(true);
    }

    public static function algo(): string|int
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }

    public static function hash(string $password): string
    {
        return password_hash($password, self::algo());
    }

    public static function passwordProblem(string $password): ?string
    {
        if (mb_strlen($password) < 10) {
            return 'Password must be at least 10 characters.';
        }
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            return 'Password must contain letters and numbers.';
        }
        return null;
    }

    public static function audit(?int $userId, string $action, string $target = ''): void
    {
        try {
            Database::insert('audit_log', [
                'user_id' => $userId,
                'action' => $action,
                'target' => mb_substr($target, 0, 120),
                'ip_hash' => Security::ipHash(),
            ]);
        } catch (Throwable $e) {
            error_log('Audit failed: ' . $e->getMessage());
        }
    }
}
