<?php
declare(strict_types=1);

/* ---------- Clients ---------- */

function admin_clients(): string
{
    Auth::require('clients');
    $q = trim((string) ($_GET['q'] ?? ''));
    $params = [];
    $where = '';
    if ($q !== '' && mb_strlen($q) <= 100) {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $where = 'WHERE c.company_name LIKE ? OR c.contact_name LIKE ? OR c.email LIKE ?';
        $params = [$like, $like, $like];
    } else {
        $q = '';
    }
    $rows = Database::all("SELECT c.id, c.company_name, c.contact_name, c.email, c.country, c.is_active, c.last_login_at,
        (SELECT COUNT(*) FROM client_projects p WHERE p.client_id = c.id) AS projects
        FROM clients c $where ORDER BY c.is_active DESC, c.company_name, c.contact_name", $params);
    return admin_view('clients', ['rows' => $rows, 'q' => $q, 'pageTitle' => 'Clients']);
}

function admin_client_edit(?string $id = null): string
{
    $user = Auth::require('clients');
    $client = null;
    if ($id !== null) {
        $client = ctype_digit($id) ? Database::one('SELECT * FROM clients WHERE id = ?', [(int) $id]) : null;
        $client ?? abort(404);
    }
    $errors = [];
    $values = $client ?? ['is_active' => 1, 'company_name' => '', 'contact_name' => '', 'email' => '', 'phone' => '', 'country' => '', 'notes' => ''];
    // Start a client from a website lead.
    if (!$client && ctype_digit((string) ($_GET['lead'] ?? '')) && Auth::can('leads', $user)) {
        $lead = Database::one('SELECT id, name, email, phone, company FROM leads WHERE id = ?', [(int) $_GET['lead']]);
        if ($lead) {
            $values = array_merge($values, ['contact_name' => $lead['name'], 'email' => $lead['email'], 'phone' => (string) $lead['phone'], 'company_name' => (string) $lead['company'], 'lead_id' => $lead['id']]);
        }
    }

    if (is_post()) {
        admin_csrf();
        $action = $_POST['action'] ?? 'save';
        if ($client && in_array($action, ['link', 'password', 'toggle'], true)) {
            $back = url('admin/clients/' . $client['id']);
            if ($action === 'link') {
                if (!$client['is_active']) {
                    flash('error', 'Turn the account on first.');
                } elseif (!Security::rateLimit('client_link', 5, 3600, (string) $client['id'])) {
                    flash('error', 'Too many links sent. Please wait an hour.');
                } else {
                    $never = $client['last_login_at'] === null;
                    $err = portal_send_link($client, $never);
                    Auth::audit((int) $user['id'], 'client_link', $client['email']);
                    $err === null ? flash('success', ($never ? 'Invitation' : 'Password link') . ' emailed to ' . $client['email'] . '.') : flash('error', 'The email failed: ' . $err);
                }
            } elseif ($action === 'password') {
                $pw = (string) ($_POST['password'] ?? '');
                if ($p = Auth::passwordProblem($pw)) {
                    flash('error', $p);
                } else {
                    Database::run('UPDATE clients SET password_hash = ?, must_change_password = 1 WHERE id = ?', [Auth::hash($pw), $client['id']]);
                    Auth::clearResetTokens('client', (int) $client['id']);
                    Auth::audit((int) $user['id'], 'client_password_set', $client['email']);
                    flash('success', 'Temporary password set. The client must change it at first sign in.');
                }
            } else {
                Database::run('UPDATE clients SET is_active = 1 - is_active WHERE id = ?', [$client['id']]);
                Auth::audit((int) $user['id'], 'client_toggle', $client['email']);
                flash('success', $client['is_active'] ? 'Portal access turned off. The client is signed out straight away.' : 'Portal access turned on.');
            }
            redirect($back);
        }
        if ($action === 'delete' && $client) {
            Auth::require('settings');
            Database::run('DELETE FROM clients WHERE id = ?', [$client['id']]);
            Auth::audit((int) $user['id'], 'client_delete', $client['email']);
            flash('success', 'Client and all their projects deleted.');
            redirect(url('admin/clients'));
        }
        $data = [];
        foreach (['company_name' => 120, 'contact_name' => 100, 'email' => 190, 'phone' => 40, 'country' => 60, 'notes' => 3000] as $k => $max) {
            $data[$k] = trim(str_replace("\r\n", "\n", (string) ($_POST[$k] ?? '')));
            if (mb_strlen($data[$k]) > $max) $errors[$k] = 'Too long.';
        }
        $data['email'] = strtolower($data['email']);
        if ($data['contact_name'] === '') $errors['contact_name'] = 'Contact name is required.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email.';
        $data['notes'] = $data['notes'] !== '' ? $data['notes'] : null;
        $values = array_merge($values, $data);
        if (!$errors) {
            try {
                if ($client) {
                    Database::update('clients', (int) $client['id'], $data);
                    $cid = (int) $client['id'];
                    Auth::audit((int) $user['id'], 'client_update', $data['email']);
                    flash('success', 'Client saved.');
                } else {
                    $data['password_hash'] = Auth::hash(bin2hex(random_bytes(24)));
                    $data['lead_id'] = ctype_digit((string) ($_POST['lead_id'] ?? '')) ? (int) $_POST['lead_id'] : null;
                    $cid = Database::insert('clients', $data);
                    Auth::audit((int) $user['id'], 'client_create', $data['email']);
                    if ($data['lead_id']) {
                        Database::run("UPDATE leads SET status = 'won' WHERE id = ?", [$data['lead_id']]);
                    }
                    $msg = 'Client added.';
                    if (($_POST['invite'] ?? '') === '1') {
                        $err = portal_send_link(['id' => $cid] + $data, true);
                        $msg = $err === null ? 'Client added and the portal invitation was emailed.' : 'Client added, but the invitation email failed: ' . $err;
                    }
                    flash('success', $msg);
                }
                redirect(url('admin/clients/' . $cid));
            } catch (PDOException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                $errors['email'] = 'Another client already uses this email.';
            }
        }
    }
    $projects = $client ? Database::all('SELECT id, title, status, progress, currency, budget FROM client_projects WHERE client_id = ? ORDER BY id DESC', [$client['id']]) : [];
    return admin_view('client-edit', ['client' => $client, 'values' => $values, 'errors' => $errors, 'projects' => $projects, 'pageTitle' => $client ? ($client['company_name'] ?: $client['contact_name']) : 'New client']);
}

/* ---------- Projects ---------- */

function admin_projects(): string
{
    Auth::require('projects');
    $status = isset(Portal::STATUSES[$_GET['status'] ?? '']) ? $_GET['status'] : '';
    $rows = Database::all('SELECT p.id, p.title, p.status, p.progress, p.currency, p.budget, p.due_date, c.company_name, c.contact_name, u.name AS manager,
        (SELECT COUNT(*) FROM project_messages m WHERE m.project_id = p.id AND m.sender_type = \'client\' AND m.read_by_staff = 0) AS unread,
        (SELECT COALESCE(SUM(CASE type WHEN \'payment\' THEN amount WHEN \'refund\' THEN -amount ELSE 0 END), 0) FROM project_transactions t WHERE t.project_id = p.id) AS paid
        FROM client_projects p JOIN clients c ON c.id = p.client_id LEFT JOIN users u ON u.id = p.manager_id ' . ($status !== '' ? 'WHERE p.status = ? ' : '') . '
        ORDER BY unread DESC, FIELD(p.status, \'in_progress\', \'review\', \'planning\', \'on_hold\', \'completed\', \'cancelled\'), p.updated_at DESC', $status !== '' ? [$status] : []);
    return admin_view('projects', ['rows' => $rows, 'status' => $status, 'pageTitle' => 'Client projects']);
}

function admin_project_find(string $id): array
{
    $p = ctype_digit($id) ? Database::one('SELECT p.*, c.contact_name, c.company_name, c.email AS client_email, c.is_active AS client_active FROM client_projects p JOIN clients c ON c.id = p.client_id WHERE p.id = ?', [(int) $id]) : null;
    return $p ?: abort(404);
}

function admin_project_edit(?string $id = null): string
{
    $user = Auth::require('projects');
    $p = $id === null ? null : admin_project_find($id);
    $errors = [];
    $values = $p ?? ['client_id' => (int) ($_GET['client'] ?? 0), 'status' => 'planning', 'progress' => 0, 'currency' => 'USD', 'budget' => '', 'manager_id' => $user['id'], 'title' => '', 'summary' => '', 'start_date' => gmdate('Y-m-d'), 'due_date' => ''];
    $clients = Database::all('SELECT id, company_name, contact_name FROM clients ORDER BY company_name, contact_name');
    $managers = Database::all("SELECT id, name FROM users WHERE is_active = 1 AND role IN ('admin', 'pm', 'sales') ORDER BY name");
    if (is_post()) {
        admin_csrf();
        if (($_POST['action'] ?? '') === 'delete' && $p) {
            Auth::require('settings');
            Database::run('DELETE FROM client_projects WHERE id = ?', [$p['id']]);
            Auth::audit((int) $user['id'], 'project_delete', '#' . $p['id']);
            flash('success', 'Project deleted.');
            redirect(url('admin/projects'));
        }
        $data = [
            'client_id' => (int) ($_POST['client_id'] ?? 0),
            'title' => trim((string) ($_POST['title'] ?? '')),
            'summary' => trim(str_replace("\r\n", "\n", (string) ($_POST['summary'] ?? ''))),
            'status' => (string) ($_POST['status'] ?? ''),
            'progress' => max(0, min(100, (int) ($_POST['progress'] ?? 0))),
            'currency' => (string) ($_POST['currency'] ?? ''),
            'budget' => trim((string) ($_POST['budget'] ?? '0')),
            'start_date' => trim((string) ($_POST['start_date'] ?? '')),
            'due_date' => trim((string) ($_POST['due_date'] ?? '')),
            'manager_id' => (int) ($_POST['manager_id'] ?? 0),
        ];
        if (!in_array($data['client_id'], array_map('intval', array_column($clients, 'id')), true)) $errors['client_id'] = 'Choose a client.';
        if ($data['title'] === '' || mb_strlen($data['title']) > 150) $errors['title'] = 'Enter a project title (150 characters at most).';
        if (mb_strlen($data['summary']) > 3000) $errors['summary'] = 'Keep the summary under 3,000 characters.';
        if (!isset(Portal::STATUSES[$data['status']])) $errors['status'] = 'Choose a status.';
        if (!isset(Portal::CURRENCIES[$data['currency']])) $errors['currency'] = 'Choose a currency.';
        $data['budget'] = str_replace(',', '', $data['budget']);
        if ($data['budget'] === '') $data['budget'] = '0';
        if (!preg_match('/^\d{1,10}(\.\d{1,2})?$/', $data['budget'])) $errors['budget'] = 'Enter the budget as a number, e.g. 12000 or 12000.50';
        foreach (['start_date', 'due_date'] as $k) {
            if ($data[$k] === '') {
                $data[$k] = null;
            } elseif (!($d = DateTime::createFromFormat('!Y-m-d', $data[$k])) || $d->format('Y-m-d') !== $data[$k]) {
                $errors[$k] = 'Enter a valid date.';
            }
        }
        if (!in_array($data['manager_id'], array_map('intval', array_column($managers, 'id')), true)) $data['manager_id'] = null;
        $data['summary'] = $data['summary'] !== '' ? $data['summary'] : null;
        $values = array_merge($values, $data);
        if (!$errors) {
            if ($p) {
                Database::update('client_projects', (int) $p['id'], $data);
                $pid = (int) $p['id'];
            } else {
                $pid = Database::insert('client_projects', $data);
            }
            Auth::audit((int) $user['id'], $p ? 'project_update' : 'project_create', '#' . $pid);
            flash('success', 'Project saved.');
            redirect(url('admin/projects/' . $pid));
        }
    }
    $extra = [];
    if ($p) {
        Database::run("UPDATE project_messages SET read_by_staff = 1 WHERE project_id = ? AND sender_type = 'client' AND read_by_staff = 0", [$p['id']]);
        $extra = [
            'totals' => Portal::totals($p),
            'updates' => Database::all('SELECT pu.id, pu.title, pu.body, pu.progress, pu.created_at, u.name FROM project_updates pu LEFT JOIN users u ON u.id = pu.user_id WHERE pu.project_id = ? ORDER BY pu.id DESC', [$p['id']]),
            'entries' => Database::all('SELECT * FROM project_transactions WHERE project_id = ? ORDER BY entry_date DESC, id DESC', [$p['id']]),
        ];
    }
    return admin_view('project-edit', $extra + [
        'p' => $p, 'values' => $values, 'errors' => $errors, 'clients' => $clients, 'managers' => $managers,
        'extraCss' => 'assets/css/portal.css', 'extraJs' => 'assets/js/portal.js',
        'pageTitle' => $p ? $p['title'] : 'New project',
    ]);
}

function admin_project_update_post(string $id): never
{
    $user = Auth::require('projects');
    admin_csrf();
    $p = admin_project_find($id);
    $title = trim((string) ($_POST['title'] ?? ''));
    $body = trim(str_replace("\r\n", "\n", (string) ($_POST['body'] ?? '')));
    $progress = ($_POST['progress'] ?? '') === '' ? null : max(0, min(100, (int) $_POST['progress']));
    if ($title === '' || mb_strlen($title) > 150 || $body === '' || mb_strlen($body) > 5000) {
        flash('error', 'An update needs a title (150 characters at most) and some text (5,000 at most).');
        redirect(url('admin/projects/' . $p['id']) . '#updates');
    }
    Database::insert('project_updates', ['project_id' => $p['id'], 'user_id' => $user['id'], 'title' => $title, 'body' => $body, 'progress' => $progress]);
    if ($progress !== null) {
        Database::run('UPDATE client_projects SET progress = ? WHERE id = ?', [$progress, $p['id']]);
    }
    if (($_POST['notify'] ?? '') === '1' && $p['client_active']) {
        $first = strtok(Emails::safeName($p['contact_name']), ' ') ?: 'there';
        [$text, $html] = Emails::notice('New update on ' . $p['title'], "Hi $first,\n\nThere is a new update on your project: $title" . ($progress !== null ? "\n\nThe project is now $progress% complete." : '') . "\n\nOpen your client portal to read it and reply.", 'Read the update', absolute_url('portal/projects/' . $p['id']));
        Mailer::queue($p['client_email'], 'Project update: ' . $p['title'], $text, $html, null, 'portal_update');
    }
    Auth::audit((int) $user['id'], 'project_update_post', '#' . $p['id']);
    flash('success', 'Update posted' . (($_POST['notify'] ?? '') === '1' ? ' and the client was emailed.' : '.'));
    redirect(url('admin/projects/' . $p['id']) . '#updates');
}

function admin_project_update_delete(string $id, string $uid): never
{
    $user = Auth::require('projects');
    admin_csrf();
    $p = admin_project_find($id);
    Database::run('DELETE FROM project_updates WHERE id = ? AND project_id = ?', [(int) $uid, $p['id']]);
    Auth::audit((int) $user['id'], 'project_update_delete', '#' . $p['id']);
    flash('success', 'Update removed.');
    redirect(url('admin/projects/' . $p['id']) . '#updates');
}

function admin_project_entry(string $id): never
{
    $user = Auth::require('finance');
    admin_csrf();
    $p = admin_project_find($id);
    $back = url('admin/projects/' . $p['id']) . '#money';
    $type = (string) ($_POST['type'] ?? '');
    $amount = str_replace(',', '', trim((string) ($_POST['amount'] ?? '')));
    $date = trim((string) ($_POST['entry_date'] ?? ''));
    $due = trim((string) ($_POST['due_date'] ?? ''));
    $method = (string) ($_POST['method'] ?? '');
    $okDate = fn ($v) => ($d = DateTime::createFromFormat('!Y-m-d', $v)) && $d->format('Y-m-d') === $v;
    $error = match (true) {
        !isset(Portal::ENTRY_TYPES[$type]) => 'Choose invoice, payment or refund.',
        !preg_match('/^\d{1,10}(\.\d{1,2})?$/', $amount) || (float) $amount <= 0 => 'Enter an amount above zero, e.g. 2500 or 2500.50',
        !$okDate($date) => 'Enter a valid date.',
        $due !== '' && !$okDate($due) => 'Enter a valid due date.',
        $method !== '' && !in_array($method, Portal::METHODS, true) => 'Choose a payment method from the list.',
        default => null,
    };
    if ($error) {
        flash('error', $error);
        redirect($back);
    }
    Database::insert('project_transactions', [
        'project_id' => $p['id'], 'type' => $type, 'amount' => $amount, 'entry_date' => $date,
        'due_date' => $type === 'invoice' && $due !== '' ? $due : null,
        'description' => mb_substr(trim((string) ($_POST['description'] ?? '')), 0, 200),
        'reference' => mb_substr(trim((string) ($_POST['reference'] ?? '')), 0, 80),
        'method' => $type === 'invoice' ? '' : $method,
        'created_by' => $user['id'],
    ]);
    Auth::audit((int) $user['id'], 'project_entry_' . $type, '#' . $p['id'] . ' ' . $amount);
    flash('success', Portal::ENTRY_TYPES[$type] . ' of ' . Portal::money($amount, $p['currency']) . ' added.');
    redirect($back);
}

function admin_project_entry_delete(string $id, string $eid): never
{
    $user = Auth::require('finance');
    admin_csrf();
    $p = admin_project_find($id);
    Database::run('DELETE FROM project_transactions WHERE id = ? AND project_id = ?', [(int) $eid, $p['id']]);
    Auth::audit((int) $user['id'], 'project_entry_delete', '#' . $p['id'] . ' entry ' . (int) $eid);
    flash('success', 'Entry deleted.');
    redirect(url('admin/projects/' . $p['id']) . '#money');
}

function admin_project_messages(string $id): never
{
    $user = Auth::user();
    if (!$user || !Auth::can('projects', $user)) {
        json_response(['ok' => false], 403);
    }
    $p = ctype_digit($id) ? Database::one('SELECT p.id, p.title, p.client_id, c.email AS client_email, c.contact_name, c.is_active FROM client_projects p JOIN clients c ON c.id = p.client_id WHERE p.id = ?', [(int) $id]) : null;
    if (!$p) {
        json_response(['ok' => false], 404);
    }
    if (!is_post()) {
        $list = Portal::messages((int) $p['id'], max(0, (int) ($_GET['after'] ?? 0)), 'staff', url('admin/project-files/'));
        if ($list) {
            Database::run("UPDATE project_messages SET read_by_staff = 1 WHERE project_id = ? AND sender_type = 'client' AND read_by_staff = 0", [$p['id']]);
        }
        json_response(['ok' => true, 'messages' => $list]);
    }
    if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
        json_response(['ok' => false, 'message' => 'Your session expired. Reload the page.'], 419);
    }
    $body = trim(str_replace("\r\n", "\n", (string) ($_POST['body'] ?? '')));
    $hasFile = ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if (($body === '' && !$hasFile) || mb_strlen($body) > 5000) {
        json_response(['ok' => false, 'message' => 'Write a message (5,000 characters at most) or attach a file.'], 422);
    }
    $file = ['', '', 0];
    if ($hasFile) {
        try {
            $file = Portal::storeFile($_FILES['file']);
        } catch (InvalidArgumentException $e) {
            json_response(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }
    Database::insert('project_messages', [
        'project_id' => $p['id'], 'sender_type' => 'staff', 'sender_id' => $user['id'], 'sender_name' => $user['name'],
        'body' => $body, 'file_stored' => $file[0], 'file_name' => $file[1], 'file_size' => $file[2], 'read_by_staff' => 1,
    ]);
    if ($p['is_active']) {
        $first = strtok(Emails::safeName($p['contact_name']), ' ') ?: 'there';
        Portal::notify($p['client_email'], 'client', (int) $p['id'], 'New message about ' . $p['title'], 'You have a new message',
            "Hi $first,\n\n" . $user['name'] . ' from ' . setting('site_name', 'AivraSol') . ' sent you a message about "' . $p['title'] . '".', 'Read and reply', absolute_url('portal/projects/' . $p['id']) . '#chat');
    }
    json_response(['ok' => true]);
}

function admin_project_file(string $id): never
{
    Auth::require('projects');
    $m = ctype_digit($id) ? Database::one('SELECT file_stored, file_name FROM project_messages WHERE id = ?', [(int) $id]) : null;
    $m ? Portal::sendFile($m) : abort(404);
}
