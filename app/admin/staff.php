<?php
declare(strict_types=1);

require_once APP_PATH . '/lib/Documents.php';

/* ---------- Employees ---------- */

function staff_find(string $id): array
{
    $emp = ctype_digit($id) ? Database::one('SELECT * FROM employees WHERE id = ?', [(int) $id]) : null;
    return $emp ?: abort(404);
}

/** HR and admins can see everyone. Anyone else only their own record. */
function staff_can_view(array $user, array $emp): bool
{
    return Auth::can('employees', $user) || ((int) ($emp['user_id'] ?? 0) === (int) $user['id']);
}

function admin_employees(): string
{
    Auth::require('employees');
    $status = isset(Documents::STATUSES[$_GET['status'] ?? '']) ? $_GET['status'] : '';
    $q = trim((string) ($_GET['q'] ?? ''));
    $where = [];
    $params = [];
    if ($status !== '') {
        $where[] = 'e.status = ?';
        $params[] = $status;
    }
    if ($q !== '' && mb_strlen($q) <= 100) {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $where[] = '(e.full_name LIKE ? OR e.employee_code LIKE ? OR e.designation LIKE ? OR e.work_email LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    } else {
        $q = '';
    }
    $rows = Database::all('SELECT e.id, e.employee_code, e.full_name, e.designation, e.department, e.status, e.join_date, e.user_id, u.role, u.is_active AS login_active
        FROM employees e LEFT JOIN users u ON u.id = e.user_id ' . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY e.status = \'active\' DESC, e.full_name', $params);
    $counts = Database::all('SELECT status, COUNT(*) AS n FROM employees GROUP BY status');
    return admin_view('employees', ['rows' => $rows, 'status' => $status, 'q' => $q, 'counts' => array_column($counts, 'n', 'status'), 'pageTitle' => 'Employees']);
}

function staff_fields(): array
{
    $offices = ['' => 'Not set'];
    foreach (Database::all('SELECT id, country, city FROM offices ORDER BY is_head_office DESC, sort_order') as $o) {
        $offices[(string) $o['id']] = trim($o['country'] . ($o['city'] !== '' ? ', ' . $o['city'] : ''));
    }
    return [
        'full_name' => ['Full name', 'text', 100, true],
        'designation' => ['Designation (job title)', 'text', 100, true],
        'department' => ['Department', 'text', 80, false],
        'employment_type' => ['Employment type', 'select', array_combine(Documents::TYPES, Documents::TYPES), true],
        'office_id' => ['Office', 'select', $offices, false],
        'status' => ['Status', 'select', Documents::STATUSES, true],
        'join_date' => ['Joining date', 'date', null, true],
        'end_date' => ['Last working day (if left)', 'date', null, false],
        'work_email' => ['Work email', 'email', 190, false],
        'personal_email' => ['Personal email', 'email', 190, false],
        'phone' => ['Phone', 'text', 40, false],
        'emergency_contact' => ['Emergency contact (name and phone)', 'text', 120, false],
        'blood_group' => ['Blood group', 'select', array_combine(Documents::BLOOD, array_map(fn ($b) => $b === '' ? 'Not set' : $b, Documents::BLOOD)), false],
        'pronoun' => ['Pronoun used in letters', 'select', Documents::PRONOUNS, true],
        'father_name' => ["Father's or guardian's name", 'text', 100, false],
        'national_id' => ['National ID / CNIC / passport number', 'text', 40, false],
        'notes' => ['Private HR notes', 'textarea', 3000, false],
    ];
}

function admin_employee_edit(?string $id = null): string
{
    $user = Auth::require('employees');
    $emp = $id === null ? null : staff_find($id);
    $fields = staff_fields();
    $errors = [];
    $values = $emp ?? ['status' => 'active', 'employment_type' => 'Full-time', 'pronoun' => 'they', 'join_date' => gmdate('Y-m-d')];
    if ($emp) {
        $values['national_id'] = Documents::nationalId($emp);
    }

    if (is_post()) {
        admin_csrf();
        if (($_POST['action'] ?? '') === 'delete' && $emp) {
            Auth::require('settings');
            Upload::deletePrivate($emp['photo']);
            Database::run('DELETE FROM employees WHERE id = ?', [$emp['id']]);
            Auth::audit((int) $user['id'], 'employee_delete', $emp['employee_code']);
            flash('success', 'Employee record deleted. Their login, if any, was kept; disable it in Team and roles.');
            redirect(url('admin/employees'));
        }
        $data = [];
        foreach ($fields as $name => [$label, $type, $opt, $required]) {
            $v = trim(str_replace("\r\n", "\n", (string) ($_POST[$name] ?? '')));
            if ($required && $v === '') {
                $errors[$name] = "$label is required.";
            }
            if ($type === 'select' && !array_key_exists($v, $opt)) {
                $errors[$name] = "Please choose a valid $label.";
            } elseif ($type === 'email' && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
                $errors[$name] = "$label must be a valid email.";
            } elseif ($type === 'date' && $v !== '') {
                $d = DateTime::createFromFormat('!Y-m-d', $v);
                if (!$d || $d->format('Y-m-d') !== $v) {
                    $errors[$name] = "$label must be a valid date.";
                }
            } elseif (is_int($opt) && mb_strlen($v) > $opt) {
                $errors[$name] = "$label is too long.";
            }
            $data[$name] = $v;
        }
        if ($data['end_date'] !== '' && $data['join_date'] !== '' && $data['end_date'] < $data['join_date']) {
            $errors['end_date'] = 'The last working day cannot be before the joining date.';
        }
        $values = array_merge($values, $data);
        $data['end_date'] = $data['end_date'] !== '' ? $data['end_date'] : null;
        $data['office_id'] = $data['office_id'] !== '' ? (int) $data['office_id'] : null;
        $data['notes'] = $data['notes'] !== '' ? $data['notes'] : null;
        $data['national_id'] = $data['national_id'] !== '' ? Secret::encrypt($data['national_id']) : '';
        $photoFile = $_FILES['photo'] ?? null;
        if (!empty($_POST['photo_remove']) && $emp) {
            Upload::deletePrivate($emp['photo']);
            $data['photo'] = '';
        } elseif ($photoFile && ($photoFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $data['photo'] = Upload::privateImage($photoFile, 'staff', 600, 'jpg', 0.8);
                if ($emp) {
                    Upload::deletePrivate($emp['photo']);
                }
            } catch (InvalidArgumentException $e) {
                $errors['photo'] = $e->getMessage();
            }
        }
        if (!$errors) {
            if ($emp) {
                Database::update('employees', (int) $emp['id'], $data);
                $newId = (int) $emp['id'];
                Auth::audit((int) $user['id'], 'employee_update', $emp['employee_code']);
            } else {
                $data['verify_token'] = Documents::token();
                $data['employee_code'] = 'TMP-' . bin2hex(random_bytes(6));
                $newId = Database::insert('employees', $data);
                $code = Documents::codePrefix() . '-' . str_pad((string) $newId, 4, '0', STR_PAD_LEFT);
                Database::run('UPDATE employees SET employee_code = ? WHERE id = ?', [$code, $newId]);
                Auth::audit((int) $user['id'], 'employee_create', $code);
            }
            flash('success', 'Employee saved.');
            redirect(url('admin/employees/' . $newId));
        }
    }

    $docs = $emp ? Database::all('SELECT d.id, d.doc_type, d.ref_no, d.title, d.issued_at, d.revoked_at, u.name AS issuer FROM employee_documents d LEFT JOIN users u ON u.id = d.issued_by WHERE d.employee_id = ? ORDER BY d.id DESC', [$emp['id']]) : [];
    $login = $emp && $emp['user_id'] ? Database::one('SELECT id, email, role, is_active, last_login_at FROM users WHERE id = ?', [$emp['user_id']]) : null;
    return admin_view('employee-edit', [
        'emp' => $emp, 'fields' => $fields, 'values' => $values, 'errors' => $errors, 'docs' => $docs, 'login' => $login,
        'pageTitle' => $emp ? $emp['full_name'] : 'New employee',
    ]);
}

/** Creates or links the employee's sign-in, or turns it off. */
function admin_employee_login(string $id): never
{
    $user = Auth::require('employees');
    admin_csrf();
    $emp = staff_find($id);
    $back = url('admin/employees/' . $emp['id']) . '#login';
    $action = $_POST['action'] ?? '';
    if ($action === 'create' && !$emp['user_id']) {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        // HR may only give the employee role; admins may give any role.
        $role = Auth::can('users', $user) && isset(Auth::ROLES[$_POST['role'] ?? '']) ? $_POST['role'] : 'employee';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Enter a valid email for the login.');
            redirect($back);
        }
        $existing = Database::one('SELECT id, role FROM users WHERE email = ?', [$email]);
        if ($existing) {
            if (Database::value('SELECT COUNT(*) FROM employees WHERE user_id = ?', [$existing['id']])) {
                flash('error', 'That login already belongs to another employee.');
                redirect($back);
            }
            if (!Auth::can('users', $user) && $existing['role'] !== 'employee') {
                flash('error', 'That email belongs to a staff account. Ask an admin to link it.');
                redirect($back);
            }
            Database::run('UPDATE employees SET user_id = ? WHERE id = ?', [$existing['id'], $emp['id']]);
            Auth::audit((int) $user['id'], 'employee_link_login', $emp['employee_code'] . ' -> ' . $email);
            flash('success', 'Existing login linked to this employee.');
            redirect($back);
        }
        $uid = Database::insert('users', ['name' => $emp['full_name'], 'email' => $email, 'password_hash' => Auth::hash(bin2hex(random_bytes(24))), 'role' => $role]);
        Database::run('UPDATE employees SET user_id = ? WHERE id = ?', [$uid, $emp['id']]);
        $err = admin_send_password_link(['id' => $uid, 'name' => $emp['full_name'], 'email' => $email, 'role' => $role], true);
        Auth::audit((int) $user['id'], 'employee_create_login', $emp['employee_code'] . ' -> ' . $email);
        $err === null ? flash('success', "Login created. A set-password link was emailed to $email.") : flash('error', 'Login created, but the email failed: ' . $err . ' Use "Email a new link" after fixing email settings.');
        redirect($back);
    }
    $login = $emp['user_id'] ? Database::one('SELECT id, name, email, role, is_active FROM users WHERE id = ?', [$emp['user_id']]) : null;
    if (!$login) {
        redirect($back);
    }
    // HR cannot change admin or other staff logins through an employee record.
    if (!Auth::can('users', $user) && $login['role'] !== 'employee') {
        flash('error', 'This login has a staff role. Ask an admin to change it.');
        redirect($back);
    }
    if ($action === 'link') {
        if (!Security::rateLimit('user_link', 5, 3600, (string) $login['id'])) {
            flash('error', 'Too many links sent. Please wait an hour.');
        } else {
            $err = admin_send_password_link($login, false);
            $err === null ? flash('success', 'A password link was emailed to ' . $login['email'] . '.') : flash('error', 'The email failed: ' . $err);
        }
    } elseif ($action === 'toggle' && (int) $login['id'] !== (int) $user['id']) {
        Database::run('UPDATE users SET is_active = 1 - is_active WHERE id = ?', [$login['id']]);
        Auth::audit((int) $user['id'], 'employee_toggle_login', $login['email']);
        flash('success', $login['is_active'] ? 'Login turned off. They are signed out straight away.' : 'Login turned on.');
    }
    redirect($back);
}

function admin_employee_photo(string $id): never
{
    $user = Auth::require('profile');
    $emp = staff_find($id);
    if (!staff_can_view($user, $emp) || !($path = Upload::privatePath($emp['photo']))) {
        abort(404);
    }
    header('Content-Type: image/jpeg');
    header('Cache-Control: private, max-age=300');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

function admin_employee_card(string $id): never
{
    $user = Auth::require('profile');
    $emp = staff_find($id);
    if (!staff_can_view($user, $emp)) {
        abort(404);
    }
    Auth::audit((int) $user['id'], 'id_card_download', $emp['employee_code']);
    Documents::idCard($emp)->send('ID-card-' . $emp['employee_code'] . '.pdf', isset($_GET['view']));
}

/** Issue an experience letter: preview the text, edit it if needed, then issue. */
function admin_employee_letter(string $id): string
{
    $user = Auth::require('employees');
    $emp = staff_find($id);
    $end = (string) ($_POST['end_date'] ?? ($emp['end_date'] ?? gmdate('Y-m-d')));
    $errors = [];
    $d = DateTime::createFromFormat('!Y-m-d', $end);
    if (!$d || $d->format('Y-m-d') !== $end || $end < $emp['join_date']) {
        $errors[] = 'Choose a valid last working day on or after the joining date.';
        $end = $emp['end_date'] ?? gmdate('Y-m-d');
    }
    if (is_post()) {
        admin_csrf();
    }
    $issuing = is_post() && ($_POST['action'] ?? '') === 'issue';
    $template = setting('doc_letter_template', Documents::DEFAULT_LETTER);
    $body = $issuing ? trim(str_replace("\r\n", "\n", (string) ($_POST['body'] ?? ''))) : strtr($template, Documents::placeholders($emp, $end));
    $title = trim((string) ($_POST['title'] ?? 'Experience Certificate'));
    if ($issuing) {
        if (mb_strlen($body) < 40 || mb_strlen($body) > 6000) $errors[] = 'The letter text must be between 40 and 6,000 characters.';
        if ($title === '' || mb_strlen($title) > 60) $errors[] = 'Give the letter a short title.';
        if (!$errors) {
            $ref = Documents::nextRef('EXP');
            $docId = Database::insert('employee_documents', [
                'employee_id' => $emp['id'],
                'doc_type' => 'experience',
                'ref_no' => $ref,
                'verify_token' => Documents::token(),
                'title' => $title,
                'data' => json_encode(Documents::letterSnapshot($emp, $end, $body), JSON_UNESCAPED_UNICODE),
                'issued_by' => $user['id'],
                'issued_at' => gmdate('Y-m-d'),
            ]);
            if (!$emp['end_date']) {
                Database::run("UPDATE employees SET end_date = ?, status = IF(status = 'active', 'resigned', status) WHERE id = ?", [$end, $emp['id']]);
            }
            Auth::audit((int) $user['id'], 'letter_issue', $ref);
            flash('success', "Letter $ref issued. Download it below.");
            redirect(url('admin/employees/' . $emp['id']) . '#documents');
        }
    }
    return admin_view('employee-letter', [
        'emp' => $emp, 'end' => $end, 'body' => $body, 'title' => $title, 'errors' => $errors,
        'ready' => setting('doc_signatory_name') !== '' && setting('doc_signature') !== '',
        'pageTitle' => 'Experience letter for ' . $emp['full_name'],
    ]);
}

function admin_document_pdf(string $id): never
{
    $user = Auth::require('profile');
    $doc = ctype_digit($id) ? Database::one('SELECT d.*, e.user_id FROM employee_documents d JOIN employees e ON e.id = d.employee_id WHERE d.id = ?', [(int) $id]) : null;
    if (!$doc || !staff_can_view($user, $doc) || ($doc['revoked_at'] && !Auth::can('employees', $user))) {
        abort(404);
    }
    Auth::audit((int) $user['id'], 'letter_download', $doc['ref_no']);
    Documents::experienceLetter($doc)->send($doc['ref_no'] . '.pdf', isset($_GET['view']));
}

function admin_document_revoke(string $id): never
{
    $user = Auth::require('employees');
    admin_csrf();
    $doc = ctype_digit($id) ? Database::one('SELECT id, employee_id, ref_no, revoked_at FROM employee_documents WHERE id = ?', [(int) $id]) : null;
    if (!$doc) {
        abort(404);
    }
    Database::run('UPDATE employee_documents SET revoked_at = IF(revoked_at IS NULL, UTC_TIMESTAMP(), NULL) WHERE id = ?', [$doc['id']]);
    Auth::audit((int) $user['id'], $doc['revoked_at'] ? 'letter_restore' : 'letter_revoke', $doc['ref_no']);
    flash('success', $doc['revoked_at'] ? 'Letter is valid again.' : 'Letter withdrawn. The verification page now shows it as not valid.');
    redirect(url('admin/employees/' . $doc['employee_id']) . '#documents');
}

/* ---------- Letters, ID cards and signature settings ---------- */

function admin_documents_settings(): string
{
    $user = Auth::require('settings');
    $errors = [];
    if (is_post()) {
        admin_csrf();
        $save = [];
        foreach (['doc_signatory_name' => 80, 'doc_signatory_title' => 80, 'doc_company_reg' => 60, 'doc_code_prefix' => 5] as $k => $max) {
            $v = trim((string) ($_POST[$k] ?? ''));
            if (mb_strlen($v) > $max) $errors[$k] = 'Too long.';
            $save[$k] = $v;
        }
        if ($save['doc_code_prefix'] !== '' && !preg_match('/^[A-Za-z]{2,5}$/', $save['doc_code_prefix'])) {
            $errors['doc_code_prefix'] = 'Use 2 to 5 letters, e.g. AVS.';
        }
        $years = (int) ($_POST['doc_card_valid_years'] ?? 2);
        $save['doc_card_valid_years'] = (string) max(1, min(5, $years));
        foreach (['doc_letter_template' => 6000, 'doc_card_back' => 300] as $k => $max) {
            $v = trim(str_replace("\r\n", "\n", (string) ($_POST[$k] ?? '')));
            if (mb_strlen($v) > $max) $errors[$k] = "Keep this under $max characters.";
            $save[$k] = $v;
        }
        foreach (['doc_signature' => 900, 'doc_stamp' => 600] as $k => $w) {
            if (!empty($_POST[$k . '_remove'])) {
                // Old files are kept so letters issued earlier still show the signature they were signed with.
                $save[$k] = '';
            } elseif (($_FILES[$k]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                try {
                    $save[$k] = Upload::privateImage($_FILES[$k], 'signatures', $w, 'png');
                } catch (InvalidArgumentException $e) {
                    $errors[$k] = $e->getMessage();
                }
            }
        }
        if (!$errors) {
            $stmt = Database::pdo()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
            foreach ($save as $k => $v) {
                $stmt->execute([$k, $v]);
            }
            Auth::audit((int) $user['id'], 'documents_settings');
            flash('success', 'Letter and ID card settings saved.');
            redirect(url('admin/documents'));
        }
    }
    return admin_view('documents-settings', ['errors' => $errors, 'pageTitle' => 'Letters and ID cards']);
}

/** Shows the saved signature or stamp inside the admin panel only. */
function admin_documents_image(string $which): never
{
    Auth::require('settings');
    $key = ['signature' => 'doc_signature', 'stamp' => 'doc_stamp'][$which] ?? abort(404);
    $path = Upload::privatePath(setting($key)) ?? abort(404);
    header('Content-Type: image/png');
    header('Cache-Control: private, no-store');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

/** Sample PDFs so admins can check the design and signature before issuing anything. */
function admin_documents_sample(string $kind): never
{
    Auth::require('settings');
    $emp = ['full_name' => 'Sample Employee', 'father_name' => '', 'pronoun' => 'they', 'national_id' => '', 'employee_code' => Documents::codePrefix() . '-0000', 'verify_token' => 'sample0000000000',
        'designation' => 'Software Engineer', 'department' => 'Engineering', 'employment_type' => 'Full-time', 'join_date' => gmdate('Y-m-d', strtotime('-2 years -3 months')), 'end_date' => null,
        'blood_group' => 'O+', 'emergency_contact' => 'Contact name, +00 000 0000000', 'phone' => '', 'photo' => ''];
    if ($kind === 'card') {
        Documents::idCard($emp)->send('sample-id-card.pdf', true);
    }
    $end = gmdate('Y-m-d');
    $body = strtr(setting('doc_letter_template', Documents::DEFAULT_LETTER), Documents::placeholders($emp, $end));
    $doc = ['ref_no' => Documents::codePrefix() . '-EXP-SAMPLE', 'verify_token' => 'sample0000000000', 'title' => 'Experience Certificate', 'issued_at' => $end,
        'data' => json_encode(Documents::letterSnapshot($emp, $end, $body))];
    Documents::experienceLetter($doc)->send('sample-experience-letter.pdf', true);
}
