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

/** Everyone who can sign in also has a person record, so People is one complete list. */
function people_sync(): void
{
    $missing = Database::all('SELECT u.id, u.name, u.email, u.role, u.created_at FROM users u LEFT JOIN employees e ON e.user_id = u.id WHERE e.id IS NULL');
    foreach ($missing as $u) {
        $id = Database::insert('employees', [
            'user_id' => $u['id'],
            'employee_code' => 'TMP-' . bin2hex(random_bytes(6)),
            'verify_token' => Documents::token(),
            'full_name' => $u['name'],
            'designation' => Auth::roleLabel($u['role']),
            'work_email' => $u['email'],
            'join_date' => substr((string) $u['created_at'], 0, 10) ?: gmdate('Y-m-d'),
            'approval' => 'approved',
        ]);
        Database::run('UPDATE employees SET employee_code = ? WHERE id = ?', [Documents::codePrefix() . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT), $id]);
    }
}

/** Account state of a person: none (no login), invited (never signed in), active or disabled. */
function people_account(array $r): string
{
    if (!$r['user_id']) {
        return 'none';
    }
    if (!(int) $r['login_active']) {
        return 'disabled';
    }
    return $r['last_login_at'] ? 'active' : 'invited';
}

const PEOPLE_ACCOUNTS = ['active' => 'Active', 'invited' => 'Invited, not signed in yet', 'disabled' => 'Disabled', 'none' => 'No login'];

function admin_employees(): string
{
    $user = Auth::require('employees');
    people_sync();
    $f = [
        'q' => trim((string) ($_GET['q'] ?? '')),
        'role' => (string) ($_GET['role'] ?? ''),
        'account' => (string) ($_GET['account'] ?? ''),
        'status' => (string) ($_GET['status'] ?? ''),
        'approval' => (string) ($_GET['approval'] ?? ''),
    ];
    $where = [];
    $params = [];
    if (isset(Documents::STATUSES[$f['status']])) {
        $where[] = 'e.status = ?';
        $params[] = $f['status'];
    } else {
        $f['status'] = '';
    }
    if (isset(Auth::ROLES[$f['role']])) {
        $where[] = 'u.role = ?';
        $params[] = $f['role'];
    } elseif ($f['role'] === 'none') {
        $where[] = 'e.user_id IS NULL';
    } else {
        $f['role'] = '';
    }
    $accountSql = ['active' => 'u.is_active = 1 AND u.last_login_at IS NOT NULL', 'invited' => 'u.is_active = 1 AND u.last_login_at IS NULL', 'disabled' => 'u.is_active = 0', 'none' => 'e.user_id IS NULL'];
    if (isset($accountSql[$f['account']])) {
        $where[] = $accountSql[$f['account']];
    } else {
        $f['account'] = '';
    }
    if (in_array($f['approval'], ['pending', 'rejected'], true)) {
        $where[] = 'e.approval = ?';
        $params[] = $f['approval'];
    } else {
        $f['approval'] = '';
    }
    if ($f['q'] !== '' && mb_strlen($f['q']) <= 100) {
        $like = '%' . addcslashes($f['q'], '%_\\') . '%';
        $where[] = '(e.full_name LIKE ? OR e.employee_code LIKE ? OR e.designation LIKE ? OR e.department LIKE ? OR e.work_email LIKE ? OR u.email LIKE ?)';
        array_push($params, $like, $like, $like, $like, $like, $like);
    } else {
        $f['q'] = '';
    }
    $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    [$page, $per, $offset] = admin_page(30);
    $total = (int) Database::value("SELECT COUNT(*) FROM employees e LEFT JOIN users u ON u.id = e.user_id $w", $params);
    $rows = Database::all("SELECT e.id, e.employee_code, e.full_name, e.designation, e.department, e.status, e.approval, e.join_date, e.photo, e.user_id,
            u.email AS login_email, u.role, u.is_active AS login_active, u.last_login_at
        FROM employees e LEFT JOIN users u ON u.id = e.user_id $w
        ORDER BY e.approval = 'pending' DESC, e.status = 'active' DESC, e.full_name LIMIT $per OFFSET $offset", $params);
    $stats = Database::one("SELECT COUNT(*) AS total, SUM(e.status = 'active') AS active, SUM(e.approval = 'pending') AS pending,
        SUM(e.user_id IS NOT NULL AND u.is_active = 1) AS logins FROM employees e LEFT JOIN users u ON u.id = e.user_id");
    $roleCounts = array_column(Database::all('SELECT role, COUNT(*) AS n FROM users GROUP BY role'), 'n', 'role');
    return admin_view('employees', [
        'rows' => $rows, 'f' => $f, 'stats' => $stats, 'roleCounts' => $roleCounts, 'total' => $total, 'page' => $page, 'per' => $per,
        'audit' => Auth::can('users', $user) ? Database::all('SELECT a.action, a.target, a.created_at, u.name FROM audit_log a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 25') : [],
        'pageTitle' => 'People',
    ]);
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
            $login = $emp['user_id'] ? Database::one('SELECT id, role, is_active FROM users WHERE id = ?', [$emp['user_id']]) : null;
            if ($login && (int) $login['id'] === (int) $user['id']) {
                flash('error', 'You cannot delete yourself.');
                redirect(url('admin/employees/' . $emp['id']));
            }
            if ($login && $login['role'] === 'admin' && (int) Database::value("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1 AND id <> ?", [$login['id']]) === 0) {
                flash('error', 'Keep at least one active admin.');
                redirect(url('admin/employees/' . $emp['id']));
            }
            Upload::deletePrivate($emp['photo']);
            Database::run('DELETE FROM employees WHERE id = ?', [$emp['id']]);
            if ($login) {
                Database::run('DELETE FROM users WHERE id = ?', [$login['id']]);
            }
            Auth::audit((int) $user['id'], 'employee_delete', $emp['employee_code']);
            flash('success', $emp['full_name'] . ' was deleted, with their account, documents and messages.');
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
        $isAdmin = Auth::can('users', $user);
        $account = [
            'email' => strtolower(trim((string) ($_POST['login_email'] ?? ''))),
            'role' => (string) ($_POST['login_role'] ?? 'employee'),
            'invite' => !empty($_POST['login_invite']),
        ];
        $values['login_email'] = $account['email'];
        $values['login_role'] = $account['role'];
        $values['login_invite'] = $account['invite'];
        if (!$emp && $account['role'] !== '') {
            // HR can only create employee accounts. Admins can give any role.
            if (!isset(Auth::ROLES[$account['role']]) || (!$isAdmin && $account['role'] !== 'employee')) {
                $errors['login_role'] = 'Choose a role you are allowed to give.';
            }
            if (!filter_var($account['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['login_email'] = 'Enter the email they will sign in with.';
            } elseif (Database::value('SELECT COUNT(*) FROM users WHERE email = ?', [$account['email']])) {
                $errors['login_email'] = 'Someone already signs in with this email. Find them in People.';
            }
            if ($data['work_email'] === '' && !isset($errors['login_email'])) {
                $data['work_email'] = $account['email'];
            }
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
                $data['approval'] = $isAdmin ? 'approved' : 'pending';
                $data['created_by'] = $user['id'];
                if ($isAdmin) {
                    $data['approved_by'] = $user['id'];
                    $data['approved_at'] = gmdate('Y-m-d H:i:s');
                }
                $newId = Database::insert('employees', $data);
                $code = Documents::codePrefix() . '-' . str_pad((string) $newId, 4, '0', STR_PAD_LEFT);
                Database::run('UPDATE employees SET employee_code = ? WHERE id = ?', [$code, $newId]);
                Auth::audit((int) $user['id'], 'employee_create', $code);
                $msg = $isAdmin ? "$code added and approved. The ID card is ready." : "$code added. An admin has been asked to approve it. The account and ID card become active after approval.";
                if ($account['role'] !== '') {
                    $uid = Database::insert('users', [
                        'name' => $data['full_name'],
                        'email' => $account['email'],
                        'password_hash' => Auth::hash(bin2hex(random_bytes(24))),
                        'role' => $account['role'],
                        'is_active' => $isAdmin ? 1 : 0,
                    ]);
                    Database::run('UPDATE employees SET user_id = ? WHERE id = ?', [$uid, $newId]);
                    Auth::audit((int) $user['id'], 'user_create', $account['email'] . ' (' . $account['role'] . ')');
                    if ($isAdmin && $account['invite']) {
                        $err = admin_send_password_link(['id' => $uid, 'name' => $data['full_name'], 'email' => $account['email'], 'role' => $account['role']], true);
                        $msg .= $err === null ? ' A set-password link was emailed to ' . $account['email'] . '.' : ' The invitation email failed: ' . $err;
                    } elseif ($isAdmin) {
                        $msg .= ' No invitation was sent. Use "Email a password link" when you are ready.';
                    }
                }
                if (!$isAdmin) {
                    people_notify_approval($user, $data['full_name'], $code, $newId);
                }
                flash('success', $msg);
                redirect(url('admin/employees/' . $newId));
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

/** Emails the admins that HR added someone who needs approval. */
function people_notify_approval(array $by, string $name, string $code, int $id): void
{
    $admins = array_column(Database::all("SELECT email FROM users WHERE role = 'admin' AND is_active = 1"), 'email');
    if (!$admins) {
        return;
    }
    [$text, $html] = Emails::notice('New team member to approve', Emails::safeName($by['name']) . " added $name ($code).\n\nPlease check the details and approve. Their account and ID card become active once you approve.", 'Review and approve', absolute_url('admin/employees/' . $id));
    Mailer::queue($admins, "Approval needed: $name", $text, $html, null, 'staff_approval');
}

/** Admins approve or reject people that HR added. Approval turns on the account, sends the invitation and unlocks the ID card. */
function admin_employee_approval(string $id): never
{
    $user = Auth::require('users');
    admin_csrf();
    $emp = staff_find($id);
    $back = url('admin/employees/' . $emp['id']);
    $login = $emp['user_id'] ? Database::one('SELECT id, name, email, role FROM users WHERE id = ?', [$emp['user_id']]) : null;
    if (($_POST['action'] ?? '') === 'approve') {
        Database::run("UPDATE employees SET approval = 'approved', approved_by = ?, approved_at = UTC_TIMESTAMP() WHERE id = ?", [$user['id'], $emp['id']]);
        $msg = $emp['full_name'] . ' is approved. The ID card is ready.';
        if ($login) {
            Database::run('UPDATE users SET is_active = 1 WHERE id = ?', [$login['id']]);
            $err = admin_send_password_link($login, true);
            $msg .= $err === null ? ' Their account is on and a set-password link was emailed to ' . $login['email'] . '.' : ' Their account is on, but the invitation email failed: ' . $err;
        }
        Auth::audit((int) $user['id'], 'employee_approve', $emp['employee_code']);
        flash('success', $msg);
    } elseif (($_POST['action'] ?? '') === 'reject') {
        Database::run("UPDATE employees SET approval = 'rejected', approved_by = ?, approved_at = UTC_TIMESTAMP() WHERE id = ?", [$user['id'], $emp['id']]);
        if ($login) {
            Database::run('UPDATE users SET is_active = 0 WHERE id = ?', [$login['id']]);
        }
        Auth::audit((int) $user['id'], 'employee_reject', $emp['employee_code']);
        flash('success', $emp['full_name'] . ' was not approved. The account stays off. You can delete the record or approve it later.');
    }
    redirect($back);
}

/** Creates or links the employee's sign-in, changes the role, or turns it off. */
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
        $approved = $emp['approval'] === 'approved';
        $uid = Database::insert('users', ['name' => $emp['full_name'], 'email' => $email, 'password_hash' => Auth::hash(bin2hex(random_bytes(24))), 'role' => $role, 'is_active' => $approved ? 1 : 0]);
        Database::run('UPDATE employees SET user_id = ? WHERE id = ?', [$uid, $emp['id']]);
        if (!$approved) {
            flash('success', 'Login created. It turns on, and the invitation is emailed, when an admin approves this person.');
            redirect($back);
        }
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
    if ($action === 'role') {
        $role = (string) ($_POST['role'] ?? '');
        $activeAdmins = (int) Database::value("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1");
        if (!Auth::can('users', $user)) {
            flash('error', 'Only an admin can change roles.');
        } elseif (!isset(Auth::ROLES[$role])) {
            flash('error', 'Choose a valid role.');
        } elseif ((int) $login['id'] === (int) $user['id']) {
            flash('error', 'You cannot change your own role. Ask another admin.');
        } elseif ($login['role'] === 'admin' && $role !== 'admin' && $login['is_active'] && $activeAdmins <= 1) {
            flash('error', 'Keep at least one active admin.');
        } else {
            Database::run('UPDATE users SET role = ? WHERE id = ?', [$role, $login['id']]);
            Auth::audit((int) $user['id'], 'user_role', $login['email'] . ' -> ' . $role);
            flash('success', $login['name'] . ' is now ' . Auth::roleLabel($role) . '.');
        }
        redirect($back);
    }
    if ($action === 'password') {
        $password = (string) ($_POST['password'] ?? '');
        if ($p = Auth::passwordProblem($password)) {
            flash('error', $p);
        } else {
            $self = (int) $login['id'] === (int) $user['id'];
            Database::run('UPDATE users SET password_hash = ?, must_change_password = ? WHERE id = ?', [Auth::hash($password), $self ? 0 : 1, $login['id']]);
            Auth::clearResetTokens('user', (int) $login['id']);
            if ($self) {
                Auth::refreshSession((int) $login['id']);
            }
            Auth::audit((int) $user['id'], 'user_password_set', $login['email']);
            flash('success', $self ? 'Your password is changed.' : 'Temporary password set. ' . $login['name'] . ' is signed out and must choose a new password at the next sign in.');
        }
        redirect($back);
    }
    if ($action === 'link') {
        if (!Security::rateLimit('user_link', 5, 3600, (string) $login['id'])) {
            flash('error', 'Too many links sent. Please wait an hour.');
        } else {
            $err = admin_send_password_link($login, false);
            $err === null ? flash('success', 'A password link was emailed to ' . $login['email'] . '.') : flash('error', 'The email failed: ' . $err);
        }
    } elseif ($action === 'toggle' && $login['role'] === 'admin' && $login['is_active'] && (int) Database::value("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1") <= 1) {
        flash('error', 'Keep at least one active admin.');
    } elseif ($action === 'toggle' && $emp['approval'] === 'pending' && !$login['is_active']) {
        flash('error', 'This person is waiting for approval. Approve them to turn the account on.');
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
    if ($emp['approval'] !== 'approved') {
        flash('error', 'The ID card is issued once an admin approves this person.');
        redirect(url(Auth::can('employees', $user) ? 'admin/employees/' . $emp['id'] : 'admin/profile'));
    }
    Auth::audit((int) $user['id'], 'id_card_download', $emp['employee_code']);
    Documents::idCard($emp)->send('ID-card-' . $emp['employee_code'] . '.pdf', isset($_GET['view']));
}

function admin_document_pdf(string $id): never
{
    $user = Auth::require('profile');
    $doc = ctype_digit($id) ? Database::one('SELECT d.*, e.user_id FROM employee_documents d JOIN employees e ON e.id = d.employee_id WHERE d.id = ?', [(int) $id]) : null;
    if (!$doc || !staff_can_view($user, $doc) || ($doc['revoked_at'] && !Auth::can('employees', $user))) {
        abort(404);
    }
    Auth::audit((int) $user['id'], 'letter_download', $doc['ref_no']);
    Documents::pdf($doc)->send($doc['ref_no'] . '.pdf', isset($_GET['view']));
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
    redirect(($_POST['back'] ?? '') === 'issuance' ? url('admin/issuance') : url('admin/employees/' . $doc['employee_id']) . '#documents');
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
        foreach (['doc_card_back' => 300] as $k => $max) {
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
    return admin_view('documents-settings', ['errors' => $errors, 'pageTitle' => 'Document design']);
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
    $code = $kind === 'certificate' ? 'APR' : 'EXP';
    $tpl = Database::one('SELECT * FROM doc_templates WHERE code = ?', [$code]) ?? ['title' => 'Experience Certificate', 'kind' => 'letter', 'salutation' => 'To whom it may concern', 'body' => Documents::DEFAULT_LETTER];
    $body = strtr($tpl['body'], Documents::placeholders($emp, $end, 'Your work on the client portal launch was outstanding.'));
    $doc = ['ref_no' => Documents::codePrefix() . '-' . $code . '-SAMPLE', 'verify_token' => 'sample0000000000', 'title' => $tpl['title'], 'issued_at' => $end,
        'data' => json_encode(Documents::letterSnapshot($emp, $end, $body, ['kind' => $tpl['kind'], 'salutation' => $tpl['salutation']]))];
    Documents::pdf($doc)->send('sample-' . strtolower($code) . '.pdf', true);
}
