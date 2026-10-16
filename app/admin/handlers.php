<?php
declare(strict_types=1);

const LEAD_STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'qualified' => 'Qualified', 'won' => 'Won', 'lost' => 'Lost', 'spam' => 'Spam'];
const APP_STATUSES = ['new' => 'New', 'reviewing' => 'Reviewing', 'interview' => 'Interview', 'offer' => 'Offer', 'hired' => 'Hired', 'rejected' => 'Rejected'];
const LEAD_TYPES = ['contact' => 'Contact', 'consultation' => 'Consultation', 'quote' => 'Quote', 'hire' => 'Hire'];

function admin_headers(): void
{
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store, max-age=0');
}

function admin_view(string $template, array $data = []): string
{
    admin_headers();
    return view('admin/' . $template, $data + ['user' => Auth::user()], 'admin/layout');
}

function admin_csrf(): void
{
    if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
        flash('error', 'Your session expired. Please try again.');
        redirect(url('admin'));
    }
}

function admin_resources(): array
{
    static $r = null;
    return $r ??= require APP_PATH . '/admin/resources.php';
}

function admin_resource(string $key): array
{
    return admin_resources()[$key] ?? abort(404);
}

function admin_launch_checklist(): array
{
    $items = [];
    $samples = (int) Database::value('SELECT COUNT(*) FROM projects WHERE is_sample = 1 AND is_active = 1');
    $items[] = [$samples === 0, $samples === 0 ? 'Portfolio has only real projects' : "Replace $samples sample portfolio projects with real work", url('admin/content/projects')];
    $t = (int) Database::value('SELECT COUNT(*) FROM testimonials WHERE is_active = 1');
    $items[] = [$t > 0, $t > 0 ? "$t client testimonials live" : 'Add real client testimonials (the section is hidden until you do)', url('admin/content/testimonials')];
    $items[] = [setting('phone') !== '' || setting('whatsapp') !== '', 'Add a phone or WhatsApp number', url('admin/settings')];
    $items[] = [setting('offices') !== '', 'Add your office addresses', url('admin/settings')];
    $items[] = [setting('contact_person_name') !== '', 'Add your sales contact person', url('admin/settings')];
    $items[] = [Content::stats() !== [], 'Add company numbers (projects, clients, team)', url('admin/settings')];
    $items[] = [(string) config('mail.host') !== '', 'Set SMTP details in app/config.php so lead emails arrive reliably', null];
    $items[] = [Security::isHttps(), 'Serve the site over HTTPS', null];
    $items[] = [!str_starts_with((string) config('app_key'), 'replace'), 'Set a unique app_key in app/config.php', null];
    return $items;
}

/* ---------- Auth pages ---------- */

function admin_login(): string
{
    if (Auth::user()) {
        redirect(url('admin'));
    }
    $error = null;
    if (is_post()) {
        if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
            $error = 'Your session expired. Please try again.';
        } else {
            $email = trim((string) ($_POST['email'] ?? ''));
            $error = Auth::attempt($email, (string) ($_POST['password'] ?? ''));
            if ($error === null) {
                redirect(url('admin'));
            }
        }
    }
    admin_headers();
    return view('admin/login', ['error' => $error ?? flash('error'), 'pageTitle' => 'Sign in'], 'admin/layout-bare');
}

function admin_logout(): never
{
    admin_csrf();
    $u = Auth::user();
    Auth::audit($u['id'] ?? null, 'logout');
    Auth::logout();
    redirect(url('admin/login'));
}

function admin_setup(): string
{
    admin_headers();
    $hasUsers = (int) Database::value('SELECT COUNT(*) FROM users') > 0;
    $token = (string) config('setup_token', '');
    if ($hasUsers || $token === '' || str_starts_with($token, 'replace')) {
        abort(404);
    }
    $errors = [];
    if (is_post()) {
        if (!Security::verifyCsrf($_POST['_csrf'] ?? null) || !hash_equals($token, (string) ($_POST['token'] ?? ''))) {
            $errors[] = 'The setup token is not correct.';
        }
        if (!Security::rateLimit('setup', 10, 3600)) {
            $errors[] = 'Too many attempts. Please wait an hour.';
        }
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        if ($name === '' || mb_strlen($name) > 80) $errors[] = 'Please enter your name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
        if ($p = Auth::passwordProblem($password)) $errors[] = $p;
        if (!$errors) {
            Database::insert('users', ['name' => $name, 'email' => $email, 'password_hash' => Auth::hash($password), 'role' => 'admin']);
            flash('error', 'Admin account created. Please sign in.');
            redirect(url('admin/login'));
        }
    }
    return view('admin/setup', ['errors' => $errors, 'pageTitle' => 'Set up admin'], 'admin/layout-bare');
}

/* ---------- Dashboard ---------- */

function admin_dashboard(): string
{
    Auth::require();
    return admin_view('dashboard', [
        'pageTitle' => 'Dashboard',
        'counts' => [
            'new_leads' => (int) Database::value("SELECT COUNT(*) FROM leads WHERE status = 'new'"),
            'leads_30' => (int) Database::value('SELECT COUNT(*) FROM leads WHERE created_at > UTC_TIMESTAMP() - INTERVAL 30 DAY AND status <> ?', ['spam']),
            'new_apps' => (int) Database::value("SELECT COUNT(*) FROM applications WHERE status = 'new'"),
            'open_jobs' => (int) Database::value('SELECT COUNT(*) FROM jobs WHERE is_active = 1'),
        ],
        'recent' => Database::all('SELECT id, type, name, email, status, created_at FROM leads ORDER BY created_at DESC LIMIT 8'),
        'checklist' => admin_launch_checklist(),
    ]);
}

/* ---------- Leads ---------- */

function admin_leads(): string
{
    Auth::require();
    [$where, $params, $filters] = admin_lead_filters();
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $per = 25;
    $total = (int) Database::value("SELECT COUNT(*) FROM leads $where", $params);
    $rows = Database::all("SELECT id, type, name, email, company, status, created_at FROM leads $where ORDER BY created_at DESC LIMIT $per OFFSET " . (($page - 1) * $per), $params);
    return admin_view('leads', compact('rows', 'total', 'page', 'per', 'filters') + ['pageTitle' => 'Leads']);
}

function admin_lead_filters(): array
{
    $where = [];
    $params = [];
    $filters = ['status' => '', 'type' => '', 'q' => ''];
    if (isset(LEAD_STATUSES[$_GET['status'] ?? ''])) {
        $filters['status'] = $_GET['status'];
        $where[] = 'status = ?';
        $params[] = $filters['status'];
    } else {
        $where[] = "status <> 'spam'";
    }
    if (isset(LEAD_TYPES[$_GET['type'] ?? ''])) {
        $filters['type'] = $_GET['type'];
        $where[] = 'type = ?';
        $params[] = $filters['type'];
    }
    $q = trim((string) ($_GET['q'] ?? ''));
    if ($q !== '' && mb_strlen($q) <= 100) {
        $filters['q'] = $q;
        $where[] = '(name LIKE ? OR email LIKE ? OR company LIKE ?)';
        $like = '%' . addcslashes($q, '%_\\') . '%';
        array_push($params, $like, $like, $like);
    }
    return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params, $filters];
}

function admin_lead(string $id): string
{
    $user = Auth::require();
    $lead = ctype_digit($id) ? Database::one('SELECT * FROM leads WHERE id = ?', [(int) $id]) : null;
    if (!$lead) {
        abort(404);
    }
    if (is_post()) {
        admin_csrf();
        if (($_POST['action'] ?? '') === 'delete') {
            Database::run('DELETE FROM leads WHERE id = ?', [$lead['id']]);
            Auth::audit((int) $user['id'], 'lead_delete', '#' . $lead['id']);
            flash('success', 'Lead deleted.');
            redirect(url('admin/leads'));
        }
        $status = isset(LEAD_STATUSES[$_POST['status'] ?? '']) ? $_POST['status'] : $lead['status'];
        $notes = mb_substr(trim((string) ($_POST['notes'] ?? '')), 0, 5000);
        Database::run('UPDATE leads SET status = ?, notes = ? WHERE id = ?', [$status, $notes, $lead['id']]);
        flash('success', 'Lead updated.');
        redirect(url('admin/leads/' . $lead['id']));
    }
    return admin_view('lead', ['lead' => $lead, 'pageTitle' => 'Lead #' . $lead['id']]);
}

function admin_leads_export(): never
{
    $user = Auth::require();
    [$where, $params] = admin_lead_filters();
    Auth::audit((int) $user['id'], 'lead_export');
    admin_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="aivrasol-leads-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['ID', 'Date (UTC)', 'Type', 'Status', 'Name', 'Email', 'Phone', 'Company', 'Message', 'Details', 'Page']);
    $stmt = Database::run("SELECT * FROM leads $where ORDER BY created_at DESC", $params);
    while ($r = $stmt->fetch()) {
        $row = [$r['id'], $r['created_at'], $r['type'], $r['status'], $r['name'], $r['email'], $r['phone'], $r['company'], $r['message'], $r['details'], $r['source_page']];
        // Stop spreadsheet formula injection.
        $row = array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v, $row);
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

/* ---------- Applications ---------- */

function admin_applications(): string
{
    Auth::require();
    $status = isset(APP_STATUSES[$_GET['status'] ?? '']) ? $_GET['status'] : '';
    $rows = $status
        ? Database::all('SELECT id, position, name, email, status, created_at FROM applications WHERE status = ? ORDER BY created_at DESC LIMIT 200', [$status])
        : Database::all('SELECT id, position, name, email, status, created_at FROM applications ORDER BY created_at DESC LIMIT 200');
    return admin_view('applications', ['rows' => $rows, 'status' => $status, 'pageTitle' => 'Job applications']);
}

function admin_application(string $id): string
{
    $user = Auth::require();
    $app = ctype_digit($id) ? Database::one('SELECT * FROM applications WHERE id = ?', [(int) $id]) : null;
    if (!$app) {
        abort(404);
    }
    if (is_post()) {
        admin_csrf();
        if (($_POST['action'] ?? '') === 'delete') {
            if ($app['cv_file'] !== '' && preg_match('/^[a-f0-9]{32}\.(pdf|docx?)$/', $app['cv_file'])) {
                @unlink(STORAGE_PATH . '/cv/' . $app['cv_file']);
            }
            Database::run('DELETE FROM applications WHERE id = ?', [$app['id']]);
            Auth::audit((int) $user['id'], 'application_delete', '#' . $app['id']);
            flash('success', 'Application and CV deleted.');
            redirect(url('admin/applications'));
        }
        $status = isset(APP_STATUSES[$_POST['status'] ?? '']) ? $_POST['status'] : $app['status'];
        Database::run('UPDATE applications SET status = ?, notes = ? WHERE id = ?', [$status, mb_substr(trim((string) ($_POST['notes'] ?? '')), 0, 5000), $app['id']]);
        flash('success', 'Application updated.');
        redirect(url('admin/applications/' . $app['id']));
    }
    return admin_view('application', ['app' => $app, 'pageTitle' => 'Application #' . $app['id']]);
}

function admin_application_cv(string $id): never
{
    $user = Auth::require();
    $app = ctype_digit($id) ? Database::one('SELECT id, cv_file, cv_original FROM applications WHERE id = ?', [(int) $id]) : null;
    if (!$app || !preg_match('/^[a-f0-9]{32}\.(pdf|docx?)$/', $app['cv_file'], $m)) {
        abort(404);
    }
    $path = STORAGE_PATH . '/cv/' . $app['cv_file'];
    if (!is_file($path)) {
        abort(404);
    }
    Auth::audit((int) $user['id'], 'cv_download', '#' . $app['id']);
    $types = ['pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    admin_headers();
    header('Content-Type: ' . $types[$m[1]]);
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: attachment; filename="cv-' . $app['id'] . '.' . $m[1] . '"');
    readfile($path);
    exit;
}

/* ---------- Generic content editor ---------- */

function admin_content_list(string $key): string
{
    Auth::require();
    $res = admin_resource($key);
    $rows = Database::all("SELECT * FROM `{$res['table']}` ORDER BY {$res['order']}");
    return admin_view('content-list', ['key' => $key, 'res' => $res, 'rows' => $rows, 'pageTitle' => $res['label']]);
}

function admin_content_edit(string $key, ?string $id = null): string
{
    $user = Auth::require();
    $res = admin_resource($key);
    $row = null;
    if ($id !== null) {
        $row = ctype_digit($id) ? Database::one("SELECT * FROM `{$res['table']}` WHERE id = ?", [(int) $id]) : null;
        if (!$row) {
            abort(404);
        }
    }
    $errors = [];
    $values = $row ?? ['is_active' => 1, 'sort_order' => 0, 'rating' => 5];

    if (is_post()) {
        admin_csrf();
        if (($_POST['action'] ?? '') === 'delete' && $row) {
            Database::run("DELETE FROM `{$res['table']}` WHERE id = ?", [$row['id']]);
            Auth::audit((int) $user['id'], 'delete_' . $key, '#' . $row['id']);
            flash('success', ucfirst($res['singular']) . ' deleted.');
            redirect(url('admin/content/' . $key));
        }
        [$data, $errors] = admin_collect_fields($res['fields'], $row);
        $values = array_merge($values, $data);
        if (!$errors) {
            try {
                if ($row) {
                    Database::update($res['table'], (int) $row['id'], $data);
                    $newId = (int) $row['id'];
                } else {
                    $newId = Database::insert($res['table'], $data);
                }
                Auth::audit((int) $user['id'], ($row ? 'update_' : 'create_') . $key, '#' . $newId);
                flash('success', ucfirst($res['singular']) . ' saved.');
                redirect(url("admin/content/$key/$newId"));
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $errors['slug'] = 'That web address is already used. Please choose another.';
                } else {
                    throw $e;
                }
            }
        }
    }
    return admin_view('content-edit', [
        'key' => $key, 'res' => $res, 'row' => $row, 'values' => $values, 'errors' => $errors,
        'pageTitle' => ($row ? 'Edit ' : 'New ') . $res['singular'],
    ]);
}

/** Reads and validates posted fields using the resource definition. */
function admin_collect_fields(array $fields, ?array $row): array
{
    $data = [];
    $errors = [];
    foreach ($fields as $name => $f) {
        $raw = $_POST[$name] ?? '';
        $value = is_string($raw) ? trim(str_replace("\r\n", "\n", $raw)) : '';
        switch ($f['type']) {
            case 'bool':
                $data[$name] = isset($_POST[$name]) ? 1 : 0;
                continue 2;
            case 'int':
                $n = filter_var($value, FILTER_VALIDATE_INT);
                $n = $n === false ? 0 : $n;
                if (isset($f['min'])) $n = max($f['min'], $n);
                if (isset($f['maxv'])) $n = min($f['maxv'], $n);
                $data[$name] = $n;
                continue 2;
            case 'image':
                $file = $_FILES[$name] ?? null;
                if (!empty($_POST[$name . '_remove'])) {
                    $data[$name] = '';
                } elseif ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    try {
                        $data[$name] = Upload::image($file, $f['folder'] ?? 'misc', $f['width'] ?? 1600);
                    } catch (InvalidArgumentException $e) {
                        $errors[$name] = $e->getMessage();
                    }
                }
                continue 2;
            case 'slug':
                $value = slugify($value);
                break;
            case 'select':
                if ($value !== '' && !array_key_exists($value, $f['options'])) {
                    $errors[$name] = 'Please choose a valid option.';
                }
                break;
            case 'url':
                if ($value !== '' && (!filter_var($value, FILTER_VALIDATE_URL) || !preg_match('#^https://#i', $value))) {
                    $errors[$name] = 'Please enter a full link starting with https://';
                }
                break;
        }
        if (!empty($f['required']) && $value === '') {
            $errors[$name] = $f['label'] . ' is required.';
        }
        if (isset($f['max']) && mb_strlen($value) > $f['max']) {
            $errors[$name] = $f['label'] . ' must be ' . $f['max'] . ' characters or fewer.';
        }
        $data[$name] = $value;
    }
    return [$data, $errors];
}

/* ---------- Settings ---------- */

function admin_settings_schema(): array
{
    return [
        'Brand and home page' => [
            'site_name' => ['Company name', 'text'],
            'site_tagline' => ['Tagline (browser title)', 'text'],
            'meta_description' => ['Default SEO description', 'textarea'],
            'hero_heading' => ['Hero heading', 'text', 'Use | to break the line, e.g. Smart software|for growing businesses'],
            'hero_text' => ['Hero text', 'textarea'],
        ],
        'Contact details' => [
            'email_info' => ['Business email', 'email'],
            'email_sales' => ['Sales email', 'email'],
            'email_hr' => ['HR email', 'email'],
            'notify_email' => ['Send new lead alerts to', 'email'],
            'phone' => ['Phone', 'text'],
            'whatsapp' => ['WhatsApp number', 'text', 'With country code, e.g. +92 300 1234567'],
            'office_hours' => ['Office hours', 'text'],
            'offices' => ['Offices', 'textarea', 'One per line, written as: City | Full address'],
            'booking_url' => ['Booking link (Calendly etc.)', 'url'],
        ],
        'Sales contact card' => [
            'contact_person_name' => ['Name', 'text'],
            'contact_person_role' => ['Role', 'text'],
            'contact_person_email' => ['Email', 'email'],
        ],
        'Company numbers (leave empty to hide)' => [
            'stat_projects' => ['Projects delivered', 'text', 'e.g. 120+'],
            'stat_clients' => ['Happy clients', 'text'],
            'stat_team' => ['Team members', 'text'],
            'stat_years' => ['Years of experience', 'text'],
        ],
        'Social links' => [
            'social_linkedin' => ['LinkedIn', 'url'],
            'social_facebook' => ['Facebook', 'url'],
            'social_instagram' => ['Instagram', 'url'],
            'social_x' => ['X (Twitter)', 'url'],
            'social_github' => ['GitHub', 'url'],
        ],
        'Analytics' => [
            'ga_measurement_id' => ['Google Analytics 4 ID', 'text', 'Looks like G-XXXXXXXXXX. Loads only after a visitor accepts analytics cookies.'],
        ],
    ];
}

function admin_settings(): string
{
    $user = Auth::require();
    $schema = admin_settings_schema();
    $errors = [];
    if (is_post()) {
        admin_csrf();
        $save = [];
        foreach ($schema as $group) {
            foreach ($group as $key => [$label, $type]) {
                $v = trim(str_replace("\r\n", "\n", (string) ($_POST[$key] ?? '')));
                if (mb_strlen($v) > 2000) $errors[$key] = "$label is too long.";
                if ($type === 'email' && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) $errors[$key] = "$label must be a valid email.";
                if ($type === 'url' && $v !== '' && !preg_match('#^https://[^\s]+$#i', $v)) $errors[$key] = "$label must start with https://";
                if ($key === 'ga_measurement_id' && $v !== '' && !preg_match('/^G-[A-Z0-9]{4,20}$/', $v)) $errors[$key] = 'Analytics ID must look like G-XXXXXXXXXX.';
                $save[$key] = $v;
            }
        }
        if (!$errors) {
            $stmt = Database::pdo()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
            foreach ($save as $k => $v) {
                $stmt->execute([$k, $v]);
            }
            Auth::audit((int) $user['id'], 'settings_update');
            flash('success', 'Settings saved.');
            redirect(url('admin/settings'));
        }
    }
    return admin_view('settings', ['schema' => $schema, 'errors' => $errors, 'posted' => is_post() ? $_POST : null, 'pageTitle' => 'Settings']);
}

/* ---------- Users and account ---------- */

function admin_users(): string
{
    $user = Auth::require('admin');
    $errors = [];
    if (is_post()) {
        admin_csrf();
        $action = $_POST['action'] ?? '';
        if ($action === 'create') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            $role = ($_POST['role'] ?? '') === 'editor' ? 'editor' : 'admin';
            if ($name === '' || mb_strlen($name) > 80) $errors[] = 'Please enter a name.';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
            if ($p = Auth::passwordProblem($password)) $errors[] = $p;
            if (!$errors) {
                try {
                    Database::insert('users', ['name' => $name, 'email' => $email, 'password_hash' => Auth::hash($password), 'role' => $role]);
                    Auth::audit((int) $user['id'], 'user_create', $email);
                    flash('success', 'User added.');
                    redirect(url('admin/users'));
                } catch (PDOException $e) {
                    $errors[] = 'A user with that email already exists.';
                }
            }
        } elseif ($action === 'toggle' && ctype_digit((string) ($_POST['id'] ?? ''))) {
            $id = (int) $_POST['id'];
            if ($id === (int) $user['id']) {
                flash('error', 'You cannot deactivate your own account.');
            } else {
                Database::run('UPDATE users SET is_active = 1 - is_active WHERE id = ?', [$id]);
                Auth::audit((int) $user['id'], 'user_toggle', '#' . $id);
                flash('success', 'User updated.');
            }
            redirect(url('admin/users'));
        }
    }
    return admin_view('users', [
        'rows' => Database::all('SELECT id, name, email, role, is_active, last_login_at FROM users ORDER BY id'),
        'errors' => $errors,
        'pageTitle' => 'Admin users',
        'audit' => Database::all('SELECT a.action, a.target, a.created_at, u.name FROM audit_log a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 25'),
    ]);
}

function admin_account(): string
{
    $user = Auth::require();
    $errors = [];
    if (is_post()) {
        admin_csrf();
        $row = Database::one('SELECT password_hash FROM users WHERE id = ?', [$user['id']]);
        $new = (string) ($_POST['new_password'] ?? '');
        if (!$row || !password_verify((string) ($_POST['current_password'] ?? ''), $row['password_hash'])) {
            $errors[] = 'Your current password is not correct.';
        } elseif ($new !== (string) ($_POST['confirm_password'] ?? '')) {
            $errors[] = 'The new passwords do not match.';
        } elseif ($p = Auth::passwordProblem($new)) {
            $errors[] = $p;
        }
        if (!$errors) {
            Database::run('UPDATE users SET password_hash = ? WHERE id = ?', [Auth::hash($new), $user['id']]);
            session_regenerate_id(true);
            Auth::audit((int) $user['id'], 'password_change');
            flash('success', 'Password changed.');
            redirect(url('admin/account'));
        }
    }
    return admin_view('account', ['errors' => $errors, 'pageTitle' => 'My account']);
}
