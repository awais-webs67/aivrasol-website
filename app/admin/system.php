<?php
declare(strict_types=1);

require_once APP_PATH . '/lib/Backup.php';

function admin_backups(): string
{
    $user = Auth::require('settings');
    if (is_post()) {
        admin_csrf();
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'database' || $action === 'full') {
                if (!Security::rateLimit('backup_make', 6, 3600, (string) $user['id'])) {
                    throw new RuntimeException('Too many backups in an hour. Please wait.');
                }
                $name = $action === 'full' ? Backup::full() : Backup::database();
                Backup::prune(max(1, (int) setting('backup_keep', '7')));
                Auth::audit((int) $user['id'], 'backup_' . $action, $name);
                flash('success', 'Backup ready: ' . $name . '. Download it and keep a copy somewhere safe.');
            } elseif ($action === 'delete' && ($p = Backup::path((string) ($_POST['name'] ?? '')))) {
                unlink($p);
                Auth::audit((int) $user['id'], 'backup_delete', basename($p));
                flash('success', 'Backup deleted.');
            } elseif ($action === 'settings') {
                $save = Database::pdo()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
                $save->execute(['backup_auto', isset($_POST['backup_auto']) ? '1' : '0']);
                $save->execute(['backup_keep', (string) max(2, min(60, (int) ($_POST['backup_keep'] ?? 7)))]);
                flash('success', 'Backup settings saved.');
            }
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
        }
        redirect(url('admin/backups'));
    }
    return admin_view('backups', ['backups' => Backup::list(), 'pageTitle' => 'Backups']);
}

function admin_backup_download(string $name): never
{
    $user = Auth::require('settings');
    $path = Backup::path($name) ?? abort(404);
    Auth::audit((int) $user['id'], 'backup_download', $name);
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    readfile($path);
    exit;
}

/* ---------- System check ---------- */

/** Runs every health check. Each result: [group, name, status ok|warn|fail, detail, how to fix]. */
function system_check_run(array $user, bool $sendEmail): array
{
    $r = [];
    $add = function (string $group, string $name, string $status, string $detail, string $fix = '') use (&$r): void {
        $r[] = [$group, $name, $status, $detail, $fix];
    };
    $try = function (callable $fn, string $group, string $name) use ($add): void {
        try {
            $fn();
        } catch (Throwable $e) {
            $add($group, $name, 'fail', 'Error: ' . mb_substr($e->getMessage(), 0, 200), 'Check the error and php-error.log in storage/logs.');
        }
    };

    // Server
    $add('Server', 'PHP version', version_compare(PHP_VERSION, '8.1', '>=') ? 'ok' : 'fail', 'PHP ' . PHP_VERSION, 'Choose PHP 8.1 or newer in cPanel, MultiPHP Manager.');
    foreach (['pdo_mysql' => 'database', 'mbstring' => 'text', 'fileinfo' => 'upload checks', 'gd' => 'images and photos', 'openssl' => 'encryption and secure email', 'zlib' => 'PDFs and backups', 'iconv' => 'PDF text'] as $ext => $why) {
        $add('Server', "PHP add-on: $ext", extension_loaded($ext) ? 'ok' : 'fail', extension_loaded($ext) ? "Installed ($why)" : "Missing, needed for $why", 'Turn it on in cPanel, Select PHP Version, Extensions (or in php.ini on XAMPP).');
    }
    $add('Server', 'PHP add-on: zip', class_exists('ZipArchive') ? 'ok' : 'warn', class_exists('ZipArchive') ? 'Installed (full backups)' : 'Missing: full backups will not work, database backups still do', 'cPanel: Select PHP Version, tick zip. XAMPP: remove the ; before extension=zip in php.ini and restart Apache.');
    $add('Server', 'WebP images', function_exists('imagewebp') ? 'ok' : 'warn', function_exists('imagewebp') ? 'Supported' : 'GD has no WebP support, image uploads may fail', 'Ask your host to enable WebP in GD.');
    $free = @disk_free_space(ROOT_PATH);
    $add('Server', 'Free disk space', $free === false ? 'warn' : ($free > 500 * 1048576 ? 'ok' : 'warn'), $free === false ? 'Could not read' : round($free / 1073741824, 1) . ' GB free', 'Delete old backups or ask your host for more space.');

    // Database
    $try(function () use ($add) {
        $version = (string) Database::value('SELECT VERSION()');
        $add('Database', 'Connection', 'ok', 'Connected (' . $version . ')');
        preg_match_all('/CREATE TABLE IF NOT EXISTS `?(\w+)`?/', (string) file_get_contents(ROOT_PATH . '/database/schema.sql'), $m);
        $have = Database::all('SHOW TABLES');
        $have = array_map(fn ($row) => (string) array_values($row)[0], $have);
        $missing = array_diff($m[1], $have);
        $add('Database', 'Tables', $missing ? 'fail' : 'ok', $missing ? 'Missing: ' . implode(', ', $missing) : count($m[1]) . ' tables present', 'Run: php database/install.php');
        $all = array_keys(require ROOT_PATH . '/database/migrations.php');
        $done = array_column(Database::all('SELECT name FROM schema_migrations'), 'name');
        $pending = array_diff($all, $done);
        $add('Database', 'Updates', $pending ? 'fail' : 'ok', $pending ? count($pending) . ' update(s) not applied' : 'Up to date', 'Run: php database/install.php');
    }, 'Database', 'Connection');

    // Folders
    foreach (['storage/logs', 'storage/sessions', 'storage/cv', 'storage/private', 'storage/backups', 'public/uploads'] as $dir) {
        $path = ROOT_PATH . '/' . $dir;
        $ok = is_dir($path) && ($t = @tempnam($path, 'chk')) !== false && @unlink($t);
        $add('Folders', $dir, $ok ? 'ok' : 'fail', $ok ? 'Writable' : 'Not writable or missing', 'In cPanel File Manager create the folder if missing and set permissions to 755.');
    }

    // Configuration
    $key = (string) config('app_key');
    $add('Configuration', 'Security key (app_key)', strlen($key) >= 32 && !str_starts_with($key, 'replace') ? 'ok' : 'fail', strlen($key) >= 32 ? 'Set' : 'Missing or too short', 'Set a 64-character random app_key in app/config.php. Keep the same key when moving hosts.');
    $base = rtrim((string) config('base_url'), '/');
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    $baseHost = (string) parse_url($base, PHP_URL_HOST) . (parse_url($base, PHP_URL_PORT) ? ':' . parse_url($base, PHP_URL_PORT) : '');
    $add('Configuration', 'Website address (base_url)', $host === '' || strcasecmp($baseHost, $host) === 0 ? 'ok' : 'warn', $base . ($host !== '' && strcasecmp($baseHost, $host) !== 0 ? " (you opened the site at $host)" : ''), 'Set base_url in app/config.php to the real address. QR codes, emails and Google links use it.');
    $https = Security::isHttps();
    $local = is_local_site();
    $add('Configuration', 'HTTPS', $https ? 'ok' : ($local ? 'warn' : 'fail'), $https ? 'Secure connection' : ($local ? 'Not needed on a test computer' : 'The site is not on HTTPS'), 'Install a free SSL in cPanel (SSL/TLS Status, Run AutoSSL), then turn on the HTTPS lines in public/.htaccess.');
    $add('Configuration', 'Mode', config('env') === 'production' || $local ? 'ok' : 'warn', 'env = ' . (string) config('env'), "Set 'env' => 'production' in app/config.php on the live site so errors are never shown to visitors.");
    $add('Configuration', 'Search indexing', Seo::indexingOn() ? 'ok' : 'warn', Seo::indexingOn() ? 'On' : 'Off: Google will not list the site', 'Admin, Settings: tick "Let search engines and AI tools index the website".');
    $add('Configuration', 'Google Search Console', setting('google_site_verification') !== '' ? 'ok' : 'warn', setting('google_site_verification') !== '' ? 'Code added' : 'Not verified yet', 'See "How this works" on the Settings page.');

    // Features
    $try(function () use ($add) {
        $e = Secret::encrypt('check-' . bin2hex(random_bytes(4)));
        $add('Features', 'Encryption', Secret::decrypt($e) !== '' ? 'ok' : 'fail', 'Saved passwords and ID numbers can be locked and unlocked', 'Check the openssl add-on and app_key.');
    }, 'Features', 'Encryption');
    $try(function () use ($add) {
        require_once APP_PATH . '/lib/Documents.php';
        $emp = ['full_name' => 'Check', 'father_name' => '', 'pronoun' => 'they', 'national_id' => '', 'employee_code' => 'CHK-0000', 'verify_token' => 'check00000000000', 'designation' => 'Test', 'department' => '', 'employment_type' => 'Full-time', 'join_date' => gmdate('Y-m-d'), 'end_date' => null, 'blood_group' => '', 'emergency_contact' => '', 'phone' => '', 'photo' => ''];
        $pdf = Documents::idCard($emp)->Output('S');
        $add('Features', 'PDF documents', str_starts_with($pdf, '%PDF') ? 'ok' : 'fail', 'ID cards and letters can be made', 'Check the zlib and iconv add-ons.');
        $sig = setting('doc_signature') !== '' && Upload::privatePath(setting('doc_signature'));
        $add('Features', 'Signature for letters', $sig ? 'ok' : 'warn', $sig ? 'Uploaded' : 'Not uploaded: letters cannot be issued', 'Admin, Letters and ID cards: add the signatory and signature.');
    }, 'Features', 'PDF documents');
    $cfg = Mailer::settings();
    $mailOk = filter_var($cfg['from_email'], FILTER_VALIDATE_EMAIL) && $cfg['host'] !== '';
    $add('Email', 'Sending settings', $mailOk ? 'ok' : 'warn', $mailOk ? 'SMTP ' . $cfg['host'] . ':' . $cfg['port'] . ' from ' . $cfg['from_email'] : 'No SMTP server or "from" email set', 'Admin, Email: fill in the mailbox details (see "How this works" there).');
    $fails = (int) Database::value("SELECT COUNT(*) FROM email_log WHERE status = 'failed' AND created_at > UTC_TIMESTAMP() - INTERVAL 1 DAY");
    $add('Email', 'Failed emails (24 hours)', $fails === 0 ? 'ok' : 'warn', $fails === 0 ? 'None' : "$fails failed", 'Open Admin, Email and read the log for the error.');
    if ($sendEmail) {
        [$subject, $text, $html] = Emails::test();
        $err = Mailer::send($user['email'], $subject, $text, $html, null, 'system_check');
        $add('Email', 'Live test email', $err === null ? 'ok' : 'fail', $err === null ? 'Sent to ' . $user['email'] . '. Check the inbox and spam folder.' : $err, 'Check the SMTP details, port and encryption in Admin, Email.');
    }

    // Background tasks
    $seen = setting('cron_last_seen');
    $age = $seen !== '' ? time() - strtotime($seen . ' UTC') : null;
    $add('Background', 'Cron job', $age !== null && $age < 900 ? 'ok' : 'warn', $age === null ? 'Never ran' : 'Last ran ' . (int) round($age / 60) . ' minutes ago', 'Add the cron job: see "How this works" on Email campaigns.');
    require_once APP_PATH . '/lib/Backup.php';
    $latest = Backup::list()[0] ?? null;
    $add('Background', 'Backups', $latest && $latest['time'] > time() - 3 * 86400 ? 'ok' : 'warn', $latest ? 'Latest: ' . gmdate('j M Y, H:i', $latest['time']) . ' UTC' : 'No backups yet', 'Admin, Backups: make one now and keep the cron job on for daily backups.');

    // Security
    if (PHP_SAPI === 'cli-server') {
        $add('Security', 'Private folders blocked', 'warn', 'Cannot test on the PHP test server', 'Run the check on XAMPP or the live server.');
    } else {
        foreach (['app/config.php', 'storage/logs/php-error.log', 'database/schema.sql'] as $p) {
            $ctx = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true, 'follow_location' => 0], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
            $body = @file_get_contents($base . '/' . $p, false, $ctx);
            $code = isset($http_response_header[0]) && preg_match('/\s(\d{3})\s?/', $http_response_header[0], $mm) ? (int) $mm[1] : 0;
            if ($code === 0) {
                $add('Security', "Blocked: /$p", 'warn', 'Could not test (the server cannot open its own address)', 'Open ' . $base . '/' . $p . ' in your browser: you should see "Forbidden" or "Not found".');
            } else {
                $leak = $code === 200 && $body !== false && (str_contains($body, '<?php') || str_contains($body, 'CREATE TABLE') || str_contains($body, 'PHP '));
                $add('Security', "Blocked: /$p", $leak ? 'fail' : 'ok', $leak ? 'Visible to the public!' : "Not public (HTTP $code)", 'Upload the .htaccess files from the project, and make sure Apache allows .htaccess (AllowOverride All).');
            }
        }
    }
    $add('Security', 'Admin accounts', (int) Database::value("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1") >= 1 ? 'ok' : 'fail', (int) Database::value("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1") . ' active admin(s)', 'Keep at least one active admin.');
    return $r;
}

function admin_system(): string
{
    $user = Auth::require('settings');
    if (is_post()) {
        admin_csrf();
        if (!Security::rateLimit('system_check', 20, 3600, (string) $user['id'])) {
            flash('error', 'Too many checks in an hour. Please wait.');
            redirect(url('admin/system'));
        }
        $results = system_check_run($user, ($_POST['email'] ?? '') === '1');
        $count = fn ($s) => count(array_filter($results, fn ($x) => $x[2] === $s));
        $id = Database::insert('system_checks', ['user_id' => $user['id'], 'passed' => $count('ok'), 'warnings' => $count('warn'), 'failed' => $count('fail'), 'results' => json_encode($results, JSON_UNESCAPED_UNICODE)]);
        Database::run('DELETE FROM system_checks WHERE id < ?', [$id - 100]);
        Auth::audit((int) $user['id'], 'system_check', $count('ok') . ' ok, ' . $count('warn') . ' warn, ' . $count('fail') . ' fail');
        redirect(url('admin/system') . '?run=' . $id);
    }
    [$runsPage, $runsPer, $runsOffset] = admin_page(10, 'runs_page');
    $runsTotal = (int) Database::value('SELECT COUNT(*) FROM system_checks');
    $runs = Database::all("SELECT s.id, s.passed, s.warnings, s.failed, s.created_at, u.name FROM system_checks s LEFT JOIN users u ON u.id = s.user_id ORDER BY s.id DESC LIMIT $runsPer OFFSET $runsOffset");
    $runId = ctype_digit((string) ($_GET['run'] ?? '')) ? (int) $_GET['run'] : (int) ($runs[0]['id'] ?? 0);
    $run = $runId ? Database::one('SELECT s.*, u.name FROM system_checks s LEFT JOIN users u ON u.id = s.user_id WHERE s.id = ?', [$runId]) : null;
    return admin_view('system', ['runsTotal' => $runsTotal, 'runsPage' => $runsPage, 'runsPer' => $runsPer, 'runs' => $runs, 'run' => $run, 'results' => $run ? (json_decode($run['results'], true) ?: []) : [], 'pageTitle' => 'System check']);
}
