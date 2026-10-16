<?php
declare(strict_types=1);

/** Client sign-in (kept apart from staff accounts) and shared helpers for client projects. */
final class Portal
{
    private const MAX_FAILS = 5;
    private const LOCK_SECONDS = 900;

    public const STATUSES = [
        'planning' => 'Planning',
        'in_progress' => 'In progress',
        'review' => 'In review',
        'on_hold' => 'On hold',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
    public const CURRENCIES = ['USD' => '$', 'GBP' => '£', 'EUR' => '€', 'AED' => 'AED ', 'AUD' => 'A$', 'PKR' => 'Rs ', 'CAD' => 'C$', 'SAR' => 'SAR '];
    public const ENTRY_TYPES = ['invoice' => 'Invoice', 'payment' => 'Payment received', 'refund' => 'Refund'];
    public const METHODS = ['Bank transfer', 'Card', 'PayPal', 'Wise', 'Payoneer', 'Stripe', 'Cash', 'Cheque', 'Other'];
    private const FILE_TYPES = [
        'application/pdf' => 'pdf',
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'text/plain' => 'txt',
        'text/csv' => 'csv',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/zip' => 'zip',
    ];

    private static array|null|false $cached = false;

    /* ---------- Sign-in ---------- */

    public static function client(): ?array
    {
        if (self::$cached !== false) {
            return self::$cached;
        }
        self::$cached = null;
        $id = (int) ($_SESSION['client_id'] ?? 0);
        if ($id <= 0) {
            return null;
        }
        $fp = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . config('app_key') . 'client');
        if (time() - (int) ($_SESSION['client_seen'] ?? 0) > 120 * 60 || !hash_equals((string) ($_SESSION['client_fp'] ?? ''), $fp)) {
            self::logout();
            flash('error', 'Your session expired. Please sign in again.');
            return null;
        }
        $row = Database::one('SELECT id, company_name, contact_name, email, must_change_password, password_hash FROM clients WHERE id = ? AND is_active = 1', [$id]);
        if (!$row || !hash_equals((string) ($_SESSION['client_pv'] ?? ''), Auth::passwordVersion($row['password_hash']))) {
            self::logout();
            return null;
        }
        $_SESSION['client_seen'] = time();
        unset($row['password_hash']);
        return self::$cached = $row;
    }

    /** Signed-in client, or a redirect to the portal sign-in page. */
    public static function require(bool $allowForced = false): array
    {
        $c = self::client() ?? redirect(url('portal/login'));
        if (!$allowForced && (int) $c['must_change_password'] === 1) {
            flash('error', 'Please choose your own password before you continue.');
            redirect(url('portal/account'));
        }
        return $c;
    }

    public static function attempt(string $email, string $password): ?string
    {
        $ipKey = Security::ipHash();
        $emailKey = hash_hmac('sha256', 'client' . strtolower($email), (string) config('app_key'));
        if (Security::rateCount('client_login_ip', $ipKey, self::LOCK_SECONDS) >= self::MAX_FAILS * 3
            || Security::rateCount('client_login_email', $emailKey, self::LOCK_SECONDS) >= self::MAX_FAILS * 2) {
            return 'Too many failed attempts. Please wait 15 minutes and try again.';
        }
        $row = Database::one('SELECT id, password_hash, is_active FROM clients WHERE email = ?', [strtolower($email)]);
        $hash = $row['password_hash'] ?? Auth::dummyHash();
        if (!password_verify($password, $hash) || !$row || (int) $row['is_active'] !== 1) {
            Security::rateLimit('client_login_ip', PHP_INT_MAX, self::LOCK_SECONDS, $ipKey);
            Security::rateLimit('client_login_email', PHP_INT_MAX, self::LOCK_SECONDS, $emailKey);
            return 'Email or password is incorrect.';
        }
        if (password_needs_rehash($row['password_hash'], Auth::algo())) {
            Database::run('UPDATE clients SET password_hash = ? WHERE id = ?', [Auth::hash($password), $row['id']]);
        }
        Security::clearRate('client_login_email', $emailKey);
        session_regenerate_id(true);
        self::$cached = false;
        $_SESSION['client_id'] = (int) $row['id'];
        $_SESSION['client_seen'] = time();
        $_SESSION['client_fp'] = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . config('app_key') . 'client');
        $_SESSION['client_pv'] = Auth::passwordVersion((string) Database::value('SELECT password_hash FROM clients WHERE id = ?', [$row['id']]));
        unset($_SESSION['_csrf']);
        Database::run('UPDATE clients SET last_login_at = UTC_TIMESTAMP() WHERE id = ?', [$row['id']]);
        return null;
    }

    public static function refreshSession(int $id): void
    {
        session_regenerate_id(true);
        $_SESSION['client_pv'] = Auth::passwordVersion((string) Database::value('SELECT password_hash FROM clients WHERE id = ?', [$id]));
        self::$cached = false;
    }

    public static function logout(): void
    {
        unset($_SESSION['client_id'], $_SESSION['client_seen'], $_SESSION['client_fp'], $_SESSION['client_pv'], $_SESSION['_csrf']);
        self::$cached = false;
        session_regenerate_id(true);
    }

    /** One client project, only when it belongs to that client. */
    public static function project(int $clientId, string $id): ?array
    {
        if (!ctype_digit($id)) {
            return null;
        }
        return Database::one('SELECT p.*, u.name AS manager_name, u.email AS manager_email FROM client_projects p LEFT JOIN users u ON u.id = p.manager_id WHERE p.id = ? AND p.client_id = ?', [(int) $id, $clientId]);
    }

    /* ---------- Money ---------- */

    public static function money(float|string $amount, string $currency): string
    {
        $sym = self::CURRENCIES[$currency] ?? ($currency . ' ');
        $n = (float) $amount;
        return ($n < 0 ? '-' : '') . $sym . number_format(abs($n), fmod(abs($n), 1.0) > 0 ? 2 : 0);
    }

    /** Budget, invoiced, paid, refunded, balance due and remaining budget for a project. */
    public static function totals(array $project): array
    {
        $sum = Database::all('SELECT type, COALESCE(SUM(amount), 0) AS total FROM project_transactions WHERE project_id = ? GROUP BY type', [$project['id']]);
        $t = array_column($sum, 'total', 'type');
        $invoiced = (float) ($t['invoice'] ?? 0);
        $paid = (float) ($t['payment'] ?? 0) - (float) ($t['refund'] ?? 0);
        $budget = (float) $project['budget'];
        return [
            'budget' => $budget,
            'invoiced' => $invoiced,
            'paid' => $paid,
            'due' => max(0, $invoiced - $paid),
            'remaining' => max(0, $budget - $paid),
            'percent' => $budget > 0 ? (int) min(100, round($paid / $budget * 100)) : 0,
        ];
    }

    /* ---------- Chat ---------- */

    /** Messages after a given id, ready for JSON. Bodies stay as plain text; the browser shows them with textContent. */
    public static function messages(int $projectId, int $after, string $viewer, string $fileBase): array
    {
        $rows = Database::all('SELECT id, sender_type, sender_name, body, file_stored, file_name, file_size, created_at FROM project_messages WHERE project_id = ? AND id > ? ORDER BY id LIMIT 200', [$projectId, $after]);
        return array_map(fn ($m) => [
            'id' => (int) $m['id'],
            'mine' => $m['sender_type'] === $viewer,
            'from' => $m['sender_type'] === 'staff' ? $m['sender_name'] . ' (' . setting('site_name', 'AivraSol') . ')' : $m['sender_name'],
            'side' => $m['sender_type'],
            'body' => $m['body'],
            'file' => $m['file_stored'] !== '' ? ['name' => $m['file_name'], 'size' => self::size((int) $m['file_size']), 'url' => $fileBase . $m['id']] : null,
            'at' => gmdate('c', strtotime($m['created_at'] . ' UTC')),
        ], $rows);
    }

    public static function size(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 1) . ' MB' : max(1, (int) round($bytes / 1024)) . ' KB';
    }

    /** Saves a chat attachment outside the web root. Returns [stored, original, size]. */
    public static function storeFile(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new InvalidArgumentException('The file could not be uploaded. Please try again.');
        }
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new InvalidArgumentException('Files must be 10 MB or smaller.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
        $original = mb_substr(preg_replace('/[^\w.\- ()]+/u', '_', basename((string) $file['name'])) ?? 'file', 0, 150);
        $ext = self::FILE_TYPES[$mime] ?? null;
        // Office files are zip archives inside; trust the zip type only with a matching Office name.
        if ($mime === 'application/zip' && preg_match('/\.(docx|xlsx|pptx)$/i', $original, $m)) {
            $ext = strtolower($m[1]);
        }
        if ($ext === null) {
            throw new InvalidArgumentException('This file type is not allowed. Use PDF, images, Word, Excel, PowerPoint, text or ZIP.');
        }
        $dir = STORAGE_PATH . '/private/portal';
        if (!is_dir($dir) && !mkdir($dir, 0750, true)) {
            throw new RuntimeException('Upload folder is not writable.');
        }
        $stored = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $stored)) {
            throw new RuntimeException('Could not save the file.');
        }
        @chmod($dir . '/' . $stored, 0640);
        return [$stored, $original, (int) $file['size']];
    }

    /** Sends a stored attachment as a download. */
    public static function sendFile(array $msg): never
    {
        $path = STORAGE_PATH . '/private/portal/' . $msg['file_stored'];
        if ($msg['file_stored'] === '' || !preg_match('/^[a-f0-9]{32}\.[a-z]{2,4}$/', $msg['file_stored']) || !is_file($path)) {
            abort(404);
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $msg['file_name']) . '"; filename*=UTF-8\'\'' . rawurlencode($msg['file_name']));
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($path);
        exit;
    }

    /** Emails at most one notice per project and audience every 15 minutes, so a busy chat does not flood inboxes. */
    public static function notify(string $to, string $audience, int $projectId, string $subject, string $title, string $body, string $button, string $link): void
    {
        if ($to === '' || !Security::rateLimit('portal_notify_' . $audience, 1, 900, (string) $projectId)) {
            return;
        }
        [$text, $html] = Emails::notice($title, $body, $button, $link);
        Mailer::queue(Mailer::addressList($to), $subject, $text, $html, null, 'portal_' . $audience);
    }
}
