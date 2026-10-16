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
    $withAddress = (int) Database::value("SELECT COUNT(*) FROM offices WHERE is_active = 1 AND (address <> '' OR city <> '')");
    $items[] = [$withAddress > 0, $withAddress > 0 ? "$withAddress offices have an address" : 'Add cities and addresses to your offices', url('admin/content/offices')];
    $items[] = [setting('contact_person_name') !== '', 'Add your sales contact person', url('admin/settings')];
    $items[] = [Content::stats() !== [], 'Add company numbers (projects, clients, team)', url('admin/settings')];
    $items[] = [Seo::indexingOn(), Seo::indexingOn() ? 'Search engines can index the website' : 'Search indexing is OFF. Turn it on in Settings before launch', url('admin/settings')];
    $items[] = [setting('google_site_verification') !== '', 'Verify the site in Google Search Console, then submit /sitemap.xml there', url('admin/settings')];
    $items[] = [setting('bing_site_verification') !== '', 'Verify the site in Bing Webmaster Tools (also feeds ChatGPT search and Copilot)', url('admin/settings')];
    $lastPost = (string) Database::value('SELECT MAX(published_at) FROM posts WHERE is_active = 1 AND published_at <= UTC_DATE()');
    $fresh = $lastPost !== '' && strtotime($lastPost) > time() - 35 * 86400;
    $items[] = [$fresh, $fresh ? 'Blog has a post from the last 5 weeks' : 'Publish a new blog post (aim for two a month)', url('admin/content/posts/new')];
    $items[] = [(string) config('mail.host') !== '', 'Set SMTP details in app/config.php so lead emails arrive reliably', null];
    $items[] = [Security::isHttps(), 'Serve the site over HTTPS', null];
    $items[] = [!str_starts_with((string) config('app_key'), 'replace'), 'Set a unique app_key in app/config.php', null];
    return $items;
}

/* ---------- Auth pages ---------- */

function admin_login(): string
{
    if ($u = Auth::user()) {
        redirect(Auth::home($u));
    }
    $error = null;
    if (is_post()) {
        if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
            $error = 'Your session expired. Please try again.';
        } else {
            $email = trim((string) ($_POST['email'] ?? ''));
            $error = Auth::attempt($email, (string) ($_POST['password'] ?? ''));
            if ($error === null) {
                redirect(Auth::home(Auth::user() ?? ['role' => '']));
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

function admin_send_password_link(array $u, bool $invite): ?string
{
    $minutes = $invite ? 2880 : 60;
    $token = Auth::createResetToken('user', (int) $u['id'], $minutes);
    $link = absolute_url('admin/reset/' . $token);
    $site = setting('site_name', 'AivraSol');
    $first = strtok(Emails::safeName($u['name']), ' ') ?: 'there';
    if ($invite) {
        $subject = "Your $site team account";
        $body = "Hi $first,\n\nAn account has been created for you on the $site team panel. Your role is " . Auth::roleLabel($u['role']) . ".\n\nUse the button below to choose your password. The link works for 48 hours and only once.";
        [$text, $html] = Emails::notice("Welcome to $site", $body, 'Set my password', $link, 'Your sign-in email is ' . $u['email'] . '. Sign in at ' . absolute_url('admin/login'));
    } else {
        $subject = "Reset your $site password";
        $body = "Hi $first,\n\nWe received a request to reset the password for your $site team account.\n\nUse the button below to choose a new one. The link works for 60 minutes and only once.";
        [$text, $html] = Emails::notice('Reset your password', $body, 'Choose a new password', $link, 'If you did not ask for this, you can ignore this email. Your password stays the same.');
    }
    return Mailer::send($u['email'], $subject, $text, $html, null, $invite ? 'invite' : 'password_reset');
}

function admin_forgot(): string
{
    admin_headers();
    $sent = false;
    $error = null;
    if (is_post()) {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
            $error = 'Your session expired. Please try again.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email.';
        } elseif (!Security::rateLimit('forgot_ip', 6, 3600)) {
            $error = 'Too many requests. Please wait an hour and try again.';
        } else {
            $u = Database::one('SELECT id, name, email, role FROM users WHERE email = ? AND is_active = 1', [$email]);
            // The same answer is shown whether or not the account exists.
            if ($u && Security::rateLimit('forgot_user', 3, 3600, (string) $u['id'])) {
                $err = admin_send_password_link($u, false);
                Auth::audit((int) $u['id'], $err === null ? 'password_reset_sent' : 'password_reset_failed', $email);
            }
            $sent = true;
        }
    }
    return view('admin/forgot', ['sent' => $sent, 'error' => $error, 'pageTitle' => 'Forgot password'], 'admin/layout-bare');
}

function admin_reset(string $token): string
{
    admin_headers();
    $id = Auth::findResetToken('user', $token);
    $u = $id ? Database::one('SELECT id, name, email FROM users WHERE id = ? AND is_active = 1', [$id]) : null;
    $errors = [];
    if ($u && is_post()) {
        $new = (string) ($_POST['new_password'] ?? '');
        if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
            $errors[] = 'Your session expired. Please try again.';
        } elseif ($new !== (string) ($_POST['confirm_password'] ?? '')) {
            $errors[] = 'The two passwords do not match.';
        } elseif ($p = Auth::passwordProblem($new)) {
            $errors[] = $p;
        }
        if (!$errors) {
            Database::run('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?', [Auth::hash($new), $u['id']]);
            Auth::clearResetTokens('user', (int) $u['id']);
            Auth::audit((int) $u['id'], 'password_reset', $u['email']);
            flash('error', 'Your new password is saved. Please sign in.');
            redirect(url('admin/login'));
        }
    }
    return view('admin/reset', ['valid' => (bool) $u, 'errors' => $errors, 'pageTitle' => 'Choose a password'], 'admin/layout-bare');
}

function admin_dashboard(): string
{
    $user = Auth::require();
    $data = ['pageTitle' => 'Dashboard', 'counts' => [], 'recent' => [], 'recentApps' => [], 'checklist' => []];
    if (Auth::can('leads', $user)) {
        $data['counts']['new_leads'] = ['New leads', (int) Database::value("SELECT COUNT(*) FROM leads WHERE status = 'new'"), 'admin/leads?status=new'];
        $data['counts']['leads_30'] = ['Leads in last 30 days', (int) Database::value('SELECT COUNT(*) FROM leads WHERE created_at > UTC_TIMESTAMP() - INTERVAL 30 DAY AND status <> ?', ['spam']), 'admin/leads'];
        $data['recent'] = Database::all('SELECT id, type, name, email, status, created_at FROM leads ORDER BY created_at DESC LIMIT 8');
    }
    if (Auth::can('applications', $user)) {
        $data['counts']['new_apps'] = ['New applications', (int) Database::value("SELECT COUNT(*) FROM applications WHERE status = 'new'"), 'admin/applications?status=new'];
        $data['recentApps'] = Database::all('SELECT id, name, email, position, status, created_at FROM applications ORDER BY created_at DESC LIMIT 6');
    }
    if (Auth::can('content:jobs', $user)) {
        $data['counts']['open_jobs'] = ['Open jobs', (int) Database::value('SELECT COUNT(*) FROM jobs WHERE is_active = 1'), 'admin/content/jobs'];
    }
    if (Auth::can('settings', $user)) {
        $data['checklist'] = admin_launch_checklist();
    }
    return admin_view('dashboard', $data);
}

/* ---------- Leads ---------- */

function admin_leads(): string
{
    Auth::require('leads');
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
    $user = Auth::require('leads');
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
    $user = Auth::require('leads');
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
    Auth::require('applications');
    $status = isset(APP_STATUSES[$_GET['status'] ?? '']) ? $_GET['status'] : '';
    $rows = $status
        ? Database::all('SELECT id, position, name, email, status, created_at FROM applications WHERE status = ? ORDER BY created_at DESC LIMIT 200', [$status])
        : Database::all('SELECT id, position, name, email, status, created_at FROM applications ORDER BY created_at DESC LIMIT 200');
    return admin_view('applications', ['rows' => $rows, 'status' => $status, 'pageTitle' => 'Job applications']);
}

function admin_application(string $id): string
{
    $user = Auth::require('applications');
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
    $user = Auth::require('applications');
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
    Auth::require('content:' . $key);
    $res = admin_resource($key);
    $rows = Database::all("SELECT * FROM `{$res['table']}` ORDER BY {$res['order']}");
    return admin_view('content-list', ['key' => $key, 'res' => $res, 'rows' => $rows, 'pageTitle' => $res['label']]);
}

function admin_content_edit(string $key, ?string $id = null): string
{
    $user = Auth::require('content:' . $key);
    $res = admin_resource($key);
    $row = null;
    if ($id !== null) {
        $row = ctype_digit($id) ? Database::one("SELECT * FROM `{$res['table']}` WHERE id = ?", [(int) $id]) : null;
        if (!$row) {
            abort(404);
        }
    }
    $errors = [];
    $values = $row ?? ['is_active' => 1, 'sort_order' => 0, 'rating' => 5, 'published_at' => gmdate('Y-m-d'), 'author_name' => setting('site_name', 'AivraSol') . ' Team'];

    if (is_post()) {
        admin_csrf();
        if (($_POST['action'] ?? '') === 'delete' && $row) {
            Database::run("DELETE FROM `{$res['table']}` WHERE id = ?", [$row['id']]);
            Auth::audit((int) $user['id'], 'delete_' . $key, '#' . $row['id']);
            if (isset($res['public'])) {
                Seo::ping([absolute_url($res['public']($row))]);
            }
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
                if (isset($res['public'])) {
                    Seo::ping([absolute_url($res['public'](array_merge($row ?? [], $data)))]);
                }
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
            case 'date':
                $d = DateTime::createFromFormat('!Y-m-d', $value);
                if ($value !== '' && (!$d || $d->format('Y-m-d') !== $value)) {
                    $errors[$name] = 'Please enter a valid date.';
                }
                if ($value === '' && empty($f['required'])) {
                    $data[$name] = null;
                    continue 2;
                }
                break;
            case 'select':
                if ($value !== '' && !array_key_exists($value, $f['options'])) {
                    $errors[$name] = 'Please choose a valid option.';
                }
                break;
            case 'email':
                if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$name] = 'Please enter a valid email address.';
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
            'hero_image' => ['Hero photo (also used on About and Careers)', 'image', 'Portrait photo works best, about 700 x 770 pixels. Leave empty to keep the current photo.'],
        ],
        'Home page sections (untick to hide)' => [
            'home_show_stats' => ['Company numbers strip', 'bool'],
            'home_show_portfolio' => ['Portfolio slider', 'bool'],
            'home_show_process' => ['How we work', 'bool'],
            'home_show_pricing' => ['Pricing', 'bool'],
            'home_show_reviews' => ['Client reviews', 'bool'],
            'home_show_faq' => ['FAQ', 'bool'],
        ],
        'Contact details' => [
            'email_info' => ['Business email', 'email'],
            'email_sales' => ['Sales email', 'email'],
            'email_hr' => ['HR email', 'email'],
            'phone' => ['Phone', 'text'],
            'whatsapp' => ['WhatsApp number', 'text', 'With country code, e.g. +92 300 1234567'],
            'office_hours' => ['Office hours', 'text'],
            'booking_url' => ['Booking link (Calendly etc.)', 'url'],
        ],
        'Sales contact card' => [
            'contact_person_name' => ['Name', 'text'],
            'contact_person_role' => ['Role', 'text'],
            'contact_person_email' => ['Email', 'email'],
            'contact_person_photo' => ['Photo', 'image', 'Square photo, at least 200 x 200 pixels.'],
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
        'Google and AI search' => [
            'search_indexing' => ['Let search engines and AI tools index the website', 'bool', 'Untick only while you test a copy of the site. Live sites must keep this on.'],
            'company_summary' => ['Company summary for Google and AI assistants', 'textarea', 'Two or three plain sentences: what you do, for whom, and where. Used in Google business data and in /llms.txt, which AI tools such as ChatGPT, Claude and Perplexity read.'],
            'company_founded' => ['Year founded', 'text', 'e.g. 2021'],
            'google_site_verification' => ['Google Search Console code', 'text', 'In Search Console choose "HTML tag" and paste the tag or just its content value.'],
            'bing_site_verification' => ['Bing Webmaster Tools code', 'text', 'Choose "Meta tag" in Bing Webmaster Tools and paste the tag or its content value. Bing also powers ChatGPT search and Copilot.'],
        ],
        'Analytics and ads (load only after cookie consent)' => [
            'ga_measurement_id' => ['Google Analytics 4 ID', 'text', 'Looks like G-XXXXXXXXXX. Loads only after a visitor accepts analytics cookies.'],
            'google_ads_id' => ['Google Ads tag ID', 'text', 'Looks like AW-123456789. Loads only after a visitor accepts marketing cookies.'],
            'google_ads_lead_label' => ['Google Ads lead conversion label', 'text', 'From Google Ads, Goals, Conversions, your lead action, Tag setup. Looks like AbC-D_efG123. Sent when a form is submitted.'],
            'meta_pixel_id' => ['Meta (Facebook and Instagram) Pixel ID', 'text', 'Numbers only, from Meta Events Manager. A Lead event is sent when a form is submitted.'],
        ],
    ];
}

function admin_settings(): string
{
    $user = Auth::require('settings');
    $schema = admin_settings_schema();
    $errors = [];
    if (is_post()) {
        admin_csrf();
        $save = [];
        foreach ($schema as $group) {
            foreach ($group as $key => [$label, $type]) {
                if ($type === 'bool') {
                    $save[$key] = isset($_POST[$key]) ? '1' : '0';
                    continue;
                }
                if ($type === 'image') {
                    if (!empty($_POST[$key . '_remove'])) {
                        $save[$key] = '';
                    } elseif (($_FILES[$key]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                        try {
                            $save[$key] = Upload::image($_FILES[$key], 'site', $key === 'contact_person_photo' ? 400 : 1400);
                        } catch (InvalidArgumentException $e) {
                            $errors[$key] = $e->getMessage();
                        }
                    }
                    continue;
                }
                $v = trim(str_replace("\r\n", "\n", (string) ($_POST[$key] ?? '')));
                if (mb_strlen($v) > 2000) $errors[$key] = "$label is too long.";
                if ($type === 'email' && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) $errors[$key] = "$label must be a valid email.";
                if ($type === 'url' && $v !== '' && !preg_match('#^https://[^\s]+$#i', $v)) $errors[$key] = "$label must start with https://";
                if (in_array($key, ['google_site_verification', 'bing_site_verification'], true) && preg_match('/content=["\']([^"\']+)["\']/i', $v, $m)) {
                    $v = $m[1];
                }
                $formats = [
                    'ga_measurement_id' => ['/^G-[A-Z0-9]{4,20}$/', 'Analytics ID must look like G-XXXXXXXXXX.'],
                    'google_ads_id' => ['/^AW-[0-9]{6,15}$/', 'Google Ads tag ID must look like AW-123456789.'],
                    'google_ads_lead_label' => ['/^[A-Za-z0-9_-]{4,64}$/', 'The conversion label can only contain letters, numbers, - and _.'],
                    'meta_pixel_id' => ['/^[0-9]{8,20}$/', 'The Pixel ID must be numbers only.'],
                    'google_site_verification' => ['/^[A-Za-z0-9_-]{10,100}$/', 'Paste the Google verification tag or its content value.'],
                    'bing_site_verification' => ['/^[A-Za-z0-9_-]{10,100}$/', 'Paste the Bing verification tag or its content value.'],
                    'company_founded' => ['/^(19|20)[0-9]{2}$/', 'Enter a four-digit year.'],
                ];
                if (isset($formats[$key]) && $v !== '' && !preg_match($formats[$key][0], $v)) {
                    $errors[$key] = $formats[$key][1];
                }
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
    $user = Auth::require('users');
    $errors = [];
    if (is_post()) {
        admin_csrf();
        $action = $_POST['action'] ?? '';
        $id = ctype_digit((string) ($_POST['id'] ?? '')) ? (int) $_POST['id'] : 0;
        $target = $id ? Database::one('SELECT id, name, email, role, is_active FROM users WHERE id = ?', [$id]) : null;
        $activeAdmins = (int) Database::value("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1");
        if ($action === 'create') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            $invite = ($_POST['invite'] ?? '') === '1';
            $role = isset(Auth::ROLES[$_POST['role'] ?? '']) ? $_POST['role'] : 'editor';
            if ($name === '' || mb_strlen($name) > 80) $errors[] = 'Please enter a name.';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
            if ($password === '' && !$invite) $errors[] = 'Enter a temporary password, or tick "Email them a link".';
            if ($password !== '' && ($p = Auth::passwordProblem($password))) $errors[] = $p;
            if (!$errors) {
                try {
                    $newId = Database::insert('users', [
                        'name' => $name,
                        'email' => $email,
                        'password_hash' => Auth::hash($password !== '' ? $password : bin2hex(random_bytes(24))),
                        'role' => $role,
                        'must_change_password' => $password !== '' ? 1 : 0,
                    ]);
                    Auth::audit((int) $user['id'], 'user_create', $email . ' (' . $role . ')');
                    $msg = 'User added.';
                    if ($invite) {
                        $err = admin_send_password_link(['id' => $newId, 'name' => $name, 'email' => $email, 'role' => $role], true);
                        $msg = $err === null ? "User added and a set-password link was emailed to $email." : 'User added, but the email failed: ' . $err;
                    }
                    flash('success', $msg);
                    redirect(url('admin/users'));
                } catch (PDOException $e) {
                    $errors[] = 'A user with that email already exists.';
                }
            }
        } elseif (!$target) {
            flash('error', 'User not found.');
            redirect(url('admin/users'));
        } elseif ($action === 'toggle') {
            if ($id === (int) $user['id']) {
                flash('error', 'You cannot disable your own account.');
            } elseif ($target['role'] === 'admin' && $target['is_active'] && $activeAdmins <= 1) {
                flash('error', 'Keep at least one active admin.');
            } else {
                Database::run('UPDATE users SET is_active = 1 - is_active WHERE id = ?', [$id]);
                Auth::audit((int) $user['id'], 'user_toggle', $target['email']);
                flash('success', $target['is_active'] ? 'User disabled. They are signed out straight away.' : 'User enabled.');
            }
            redirect(url('admin/users'));
        } elseif ($action === 'role') {
            $role = (string) ($_POST['role'] ?? '');
            if (!isset(Auth::ROLES[$role])) {
                flash('error', 'Choose a valid role.');
            } elseif ($id === (int) $user['id']) {
                flash('error', 'You cannot change your own role. Ask another admin.');
            } elseif ($target['role'] === 'admin' && $role !== 'admin' && $activeAdmins <= 1) {
                flash('error', 'Keep at least one active admin.');
            } else {
                Database::run('UPDATE users SET role = ? WHERE id = ?', [$role, $id]);
                Auth::audit((int) $user['id'], 'user_role', $target['email'] . ' -> ' . $role);
                flash('success', $target['name'] . ' is now ' . Auth::roleLabel($role) . '.');
            }
            redirect(url('admin/users'));
        } elseif ($action === 'password') {
            $password = (string) ($_POST['password'] ?? '');
            if ($p = Auth::passwordProblem($password)) {
                flash('error', $p);
            } else {
                Database::run('UPDATE users SET password_hash = ?, must_change_password = ? WHERE id = ?', [Auth::hash($password), $id === (int) $user['id'] ? 0 : 1, $id]);
                Auth::clearResetTokens('user', $id);
                if ($id === (int) $user['id']) {
                    Auth::refreshSession($id);
                }
                Auth::audit((int) $user['id'], 'user_password_set', $target['email']);
                flash('success', 'Temporary password set. ' . $target['name'] . ' will be asked to change it at the next sign in.');
            }
            redirect(url('admin/users'));
        } elseif ($action === 'link') {
            if (!$target['is_active']) {
                flash('error', 'Enable the user first.');
            } elseif (!Security::rateLimit('user_link', 5, 3600, (string) $id)) {
                flash('error', 'Too many links sent to this user. Please wait an hour.');
            } else {
                $err = admin_send_password_link($target, false);
                Auth::audit((int) $user['id'], 'user_reset_link', $target['email']);
                $err === null ? flash('success', 'Password reset link emailed to ' . $target['email'] . '.') : flash('error', 'The email failed: ' . $err);
            }
            redirect(url('admin/users'));
        }
    }
    return admin_view('users', [
        'rows' => Database::all('SELECT id, name, email, role, is_active, must_change_password, last_login_at FROM users ORDER BY is_active DESC, name'),
        'errors' => $errors,
        'pageTitle' => 'Team and roles',
        'audit' => Database::all('SELECT a.action, a.target, a.created_at, u.name FROM audit_log a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 25'),
    ]);
}

function admin_profile(): string
{
    $user = Auth::require('profile');
    $errors = [];
    $forced = (int) $user['must_change_password'] === 1;
    if (is_post()) {
        admin_csrf();
        if (($_POST['action'] ?? '') === 'name') {
            $name = trim((string) ($_POST['name'] ?? ''));
            if ($name === '' || mb_strlen($name) > 80) {
                $errors[] = 'Please enter your name (80 characters at most).';
            } else {
                Database::run('UPDATE users SET name = ? WHERE id = ?', [$name, $user['id']]);
                flash('success', 'Your name is updated.');
                redirect(url('admin/profile'));
            }
        } else {
            $row = Database::one('SELECT password_hash FROM users WHERE id = ?', [$user['id']]);
            $new = (string) ($_POST['new_password'] ?? '');
            if (!$row || !password_verify((string) ($_POST['current_password'] ?? ''), $row['password_hash'])) {
                $errors[] = 'Your current password is not correct.';
            } elseif ($new !== (string) ($_POST['confirm_password'] ?? '')) {
                $errors[] = 'The new passwords do not match.';
            } elseif (password_verify($new, $row['password_hash'])) {
                $errors[] = 'Choose a password that is different from the current one.';
            } elseif ($p = Auth::passwordProblem($new)) {
                $errors[] = $p;
            }
            if (!$errors) {
                Database::run('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?', [Auth::hash($new), $user['id']]);
                Auth::clearResetTokens('user', (int) $user['id']);
                Auth::refreshSession((int) $user['id']);
                Auth::audit((int) $user['id'], 'password_change');
                flash('success', 'Password changed. Any other device signed in with your account has been signed out.');
                redirect($forced ? Auth::home($user) : url('admin/profile'));
            }
        }
    }
    $employee = Database::one('SELECT * FROM employees WHERE user_id = ?', [$user['id']]);
    $letters = $employee ? Database::all('SELECT id, ref_no, title, issued_at FROM employee_documents WHERE employee_id = ? AND revoked_at IS NULL ORDER BY id DESC', [$employee['id']]) : [];
    return admin_view('profile', ['errors' => $errors, 'forced' => $forced, 'employee' => $employee, 'letters' => $letters, 'pageTitle' => 'My profile']);
}

/* ---------- Email settings ---------- */

function admin_email(): string
{
    $user = Auth::require('settings');
    $errors = [];
    $testResult = null;
    if (is_post()) {
        admin_csrf();
        $action = $_POST['action'] ?? '';
        if ($action === 'test') {
            $to = trim((string) ($_POST['test_to'] ?? ''));
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                flash('error', 'Enter a valid email address for the test.');
            } elseif (!Security::rateLimit('email_test', 10, 600, (string) $user['id'])) {
                flash('error', 'Too many test emails. Please wait a few minutes.');
            } else {
                [$subject, $text, $html] = Emails::test();
                $err = Mailer::send($to, $subject, $text, $html, null, 'test');
                $err === null ? flash('success', "Test email sent to $to. Check the inbox and the spam folder.") : flash('error', 'The test email failed: ' . $err);
            }
            redirect(url('admin/email'));
        }
        $save = [];
        foreach (['notify_emails' => 'Enquiry recipients', 'notify_emails_hr' => 'Job application recipients'] as $k => $label) {
            $raw = (string) ($_POST[$k] ?? '');
            $list = Mailer::addressList($raw);
            $typed = preg_split('/[\s,;]+/', trim($raw)) ?: [];
            if (count(array_filter($typed)) !== count($list)) {
                $errors[$k] = "$label: one or more addresses are not valid.";
            } elseif (count($list) > 15) {
                $errors[$k] = "$label: add 15 addresses at most.";
            }
            $save[$k] = implode("\n", $list);
        }
        $save['mail_source'] = ($_POST['mail_source'] ?? '') === 'custom' ? 'custom' : 'config';
        $save['mail_from_email'] = trim((string) ($_POST['mail_from_email'] ?? ''));
        $save['mail_from_name'] = mb_substr(trim((string) ($_POST['mail_from_name'] ?? '')), 0, 80);
        $save['smtp_host'] = preg_replace('/[^a-z0-9.\-]/i', '', (string) ($_POST['smtp_host'] ?? '')) ?? '';
        $save['smtp_port'] = (string) max(1, min(65535, (int) ($_POST['smtp_port'] ?? 465)));
        $save['smtp_encryption'] = in_array($_POST['smtp_encryption'] ?? '', ['ssl', 'tls', 'none'], true) ? $_POST['smtp_encryption'] : 'ssl';
        $save['smtp_username'] = mb_substr(trim((string) ($_POST['smtp_username'] ?? '')), 0, 190);
        if ($save['mail_from_email'] !== '' && !filter_var($save['mail_from_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['mail_from_email'] = 'The "from" address is not a valid email.';
        }
        if ($save['mail_source'] === 'custom' && $save['mail_from_email'] === '') {
            $errors['mail_from_email'] = 'Add the address emails are sent from.';
        }
        $newPassword = (string) ($_POST['smtp_password'] ?? '');
        if ($newPassword !== '') {
            $save['smtp_password'] = Secret::encrypt($newPassword);
        } elseif (!empty($_POST['smtp_password_clear'])) {
            $save['smtp_password'] = '';
        }
        $save['autoreply_enabled'] = isset($_POST['autoreply_enabled']) ? '1' : '0';
        foreach (array_keys(Emails::TYPES) as $t) {
            $subj = trim((string) ($_POST["autoreply_{$t}_subject"] ?? ''));
            $body = trim(str_replace("\r\n", "\n", (string) ($_POST["autoreply_{$t}_body"] ?? '')));
            if ($subj === '' || $body === '') {
                $errors["autoreply_$t"] = 'Each automatic reply needs a subject and a message.';
            }
            $save["autoreply_{$t}_subject"] = mb_substr($subj, 0, 150);
            $save["autoreply_{$t}_body"] = mb_substr($body, 0, 3000);
        }
        if (!$errors) {
            $stmt = Database::pdo()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
            foreach ($save as $k => $v) {
                $stmt->execute([$k, $v]);
            }
            Auth::audit((int) $user['id'], 'email_settings_update');
            flash('success', 'Email settings saved. Send a test email to check them.');
            redirect(url('admin/email'));
        }
    }
    return admin_view('email', [
        'pageTitle' => 'Email settings',
        'errors' => $errors,
        'posted' => is_post() ? $_POST : null,
        'effective' => Mailer::settings(),
        'log' => Database::all('SELECT type, recipients, subject, status, error, created_at FROM email_log ORDER BY id DESC LIMIT 30'),
        'user' => $user,
    ]);
}
