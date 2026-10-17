<?php
declare(strict_types=1);

final class Auth
{
    private const MAX_FAILS = 5;
    private const LOCK_SECONDS = 900;

    /** Roles and what each one may open. "content" covers every website content editor. */
    public const ROLES = [
        'admin' => ['label' => 'Admin', 'about' => 'Full access to everything', 'caps' => ['*']],
        'hr' => ['label' => 'HR', 'about' => 'Job applications, people, messages to the team, certificates, ID cards and job posts. People HR adds need an admin\'s approval', 'caps' => ['dashboard', 'applications', 'employees', 'content:jobs', 'profile']],
        'sales' => ['label' => 'Sales', 'about' => 'Leads, clients and email campaigns', 'caps' => ['dashboard', 'leads', 'clients', 'campaigns', 'profile']],
        'pm' => ['label' => 'Project manager', 'about' => 'Clients, projects, updates, chat and payments', 'caps' => ['dashboard', 'clients', 'projects', 'finance', 'profile']],
        'editor' => ['label' => 'Content editor', 'about' => 'Website content, portfolio and blog', 'caps' => ['dashboard', 'content', 'blog', 'profile']],
        'employee' => ['label' => 'Employee', 'about' => 'Own profile, ID card, documents and messages only', 'caps' => ['profile']],
    ];

    public static function can(string $cap, ?array $user = null): bool
    {
        $user ??= self::user();
        if (!$user) {
            return false;
        }
        $caps = self::ROLES[$user['role']]['caps'] ?? [];
        if (in_array('*', $caps, true) || in_array($cap, $caps, true)) {
            return true;
        }
        return str_starts_with($cap, 'content:') && in_array('content', $caps, true);
    }

    /** Where a user lands after signing in. */
    public static function home(array $user): string
    {
        return self::can('dashboard', $user) ? url('admin') : url('admin/profile');
    }

    private static array|null|false $cached = false;

    public static function user(): ?array
    {
        if (self::$cached !== false) {
            return self::$cached;
        }
        self::$cached = null;
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
        $row = Database::one('SELECT id, name, email, role, must_change_password, password_hash FROM users WHERE id = ? AND is_active = 1', [$id]);
        // A password change or reset signs out every other session of that user.
        if (!$row || !hash_equals((string) ($_SESSION['admin_pv'] ?? ''), self::passwordVersion($row['password_hash']))) {
            self::logout();
            return null;
        }
        unset($row['password_hash']);
        self::$cached = $row;
        return self::$cached;
    }

    /** Short fingerprint of the stored hash, kept in the session. */
    public static function passwordVersion(string $hash): string
    {
        return substr(hash_hmac('sha256', $hash, (string) config('app_key')), 0, 32);
    }

    /** Call after the signed-in user changes their own password so this session stays valid. */
    public static function refreshSession(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['admin_pv'] = self::passwordVersion((string) Database::value('SELECT password_hash FROM users WHERE id = ?', [$userId]));
        self::$cached = false;
    }

    /** Signed-in staff member with the given permission, or a redirect / 403. */
    public static function require(string $cap = 'dashboard'): array
    {
        $user = self::user();
        if (!$user) {
            redirect(url('admin/login'));
        }
        if ((int) $user['must_change_password'] === 1 && $cap !== 'profile') {
            flash('error', 'Please choose a new password before you continue.');
            redirect(url('admin/profile'));
        }
        if (!self::can($cap, $user)) {
            if ($cap === 'dashboard') {
                redirect(url('admin/profile'));
            }
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
            || Security::rateCount('login_email', $emailKey, self::LOCK_SECONDS) >= self::MAX_FAILS * 2) {
            return 'Too many failed attempts. Please wait 15 minutes and try again.';
        }

        $user = Database::one('SELECT id, password_hash, is_active FROM users WHERE email = ?', [strtolower($email)]);
        // Verify against a dummy hash when the user does not exist so timing does not reveal valid emails.
        $hash = $user['password_hash'] ?? self::dummyHash();
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
        self::$cached = false;
        $_SESSION['admin_id'] = (int) $user['id'];
        $_SESSION['admin_seen'] = time();
        $_SESSION['admin_fp'] = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . config('app_key'));
        $_SESSION['admin_pv'] = self::passwordVersion((string) Database::value('SELECT password_hash FROM users WHERE id = ?', [$user['id']]));
        unset($_SESSION['_csrf']);
        Database::run('UPDATE users SET last_login_at = UTC_TIMESTAMP() WHERE id = ?', [$user['id']]);
        self::audit((int) $user['id'], 'login', $email);
        return null;
    }

    public static function logout(): void
    {
        unset($_SESSION['admin_id'], $_SESSION['admin_seen'], $_SESSION['admin_fp'], $_SESSION['admin_pv'], $_SESSION['_csrf']);
        self::$cached = false;
        session_regenerate_id(true);
    }

    /** A hash made with the same algorithm as real ones, so unknown emails take the same time to check. */
    public static function dummyHash(): string
    {
        static $h = null;
        return $h ??= self::hash(bin2hex(random_bytes(8)));
    }

    /** Lets someone sign in straight after a password reset, even if their email was locked by wrong guesses. */
    public static function clearLoginLock(string $email, string $prefix = ''): void
    {
        Security::clearRate($prefix . 'login_email', hash_hmac('sha256', ($prefix === 'client_' ? 'client' : '') . strtolower($email), (string) config('app_key')));
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

    /** One-time link token for setting a password. Only its hash is stored. */
    public static function createResetToken(string $type, int $accountId, int $minutes): string
    {
        Database::run('DELETE FROM password_resets WHERE (account_type = ? AND account_id = ?) OR expires_at < UTC_TIMESTAMP() - INTERVAL 7 DAY', [$type, $accountId]);
        $token = bin2hex(random_bytes(32));
        Database::insert('password_resets', [
            'account_type' => $type,
            'account_id' => $accountId,
            'token_hash' => hash('sha256', $token),
            'expires_at' => gmdate('Y-m-d H:i:s', time() + $minutes * 60),
        ]);
        return $token;
    }

    /** The account id for a valid, unused, unexpired token, or null. */
    public static function findResetToken(string $type, string $token): ?int
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $id = Database::value('SELECT account_id FROM password_resets WHERE account_type = ? AND token_hash = ? AND used_at IS NULL AND expires_at > UTC_TIMESTAMP()', [$type, hash('sha256', $token)]);
        return $id === null || $id === false ? null : (int) $id;
    }

    public static function clearResetTokens(string $type, int $accountId): void
    {
        Database::run('DELETE FROM password_resets WHERE account_type = ? AND account_id = ?', [$type, $accountId]);
    }

    public static function roleLabel(string $role): string
    {
        return self::ROLES[$role]['label'] ?? ucfirst($role);
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
