<?php
declare(strict_types=1);

/* Client portal: /portal. Every query is limited to the signed-in client's own records. */

function portal_view(string $template, array $data = []): string
{
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store, max-age=0');
    return view('portal/' . $template, $data + ['client' => Portal::client()], 'portal/layout');
}

function portal_csrf(string $back = 'portal'): void
{
    if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
        flash('error', 'Your session expired. Please try again.');
        redirect(url($back));
    }
}

function portal_login(): string
{
    if (Portal::client()) {
        redirect(url('portal'));
    }
    $error = null;
    if (is_post()) {
        if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
            $error = 'Your session expired. Please try again.';
        } else {
            $error = Portal::attempt(trim((string) ($_POST['email'] ?? '')), (string) ($_POST['password'] ?? ''));
            if ($error === null) {
                redirect(url('portal'));
            }
        }
    }
    return portal_view('login', ['error' => $error ?? flash('error'), 'pageTitle' => 'Client sign in', 'bare' => true]);
}

function portal_logout(): never
{
    portal_csrf();
    Portal::logout();
    redirect(url('portal/login'));
}

function portal_send_link(array $c, bool $invite, bool $later = false): ?string
{
    $token = Auth::createResetToken('client', (int) $c['id'], $invite ? 7 * 1440 : 60);
    $link = absolute_url('portal/reset/' . $token);
    $site = setting('site_name', 'AivraSol');
    $first = strtok(Emails::safeName($c['contact_name']), ' ') ?: 'there';
    if ($invite) {
        $body = "Hi $first,\n\nYour $site client portal is ready. In one place you can:\n- see the progress and updates on your project\n- message our team and share files\n- check your budget, invoices and payments\n\nUse the button below to choose your password. The link works for 7 days and only once.";
        [$text, $html] = Emails::notice("Welcome to your $site client portal", $body, 'Set my password', $link, 'Your sign-in email is ' . $c['email'] . '. Portal address: ' . absolute_url('portal'));
        return Mailer::send($c['email'], "Your $site client portal", $text, $html, null, 'client_invite');
    }
    $body = "Hi $first,\n\nWe received a request to reset the password for your $site client portal.\n\nUse the button below to choose a new one. The link works for 60 minutes and only once.";
    [$text, $html] = Emails::notice('Reset your password', $body, 'Choose a new password', $link, 'If you did not ask for this, you can ignore this email.');
    if ($later) {
        Mailer::queue($c['email'], "Reset your $site portal password", $text, $html, null, 'client_reset');
        return null;
    }
    return Mailer::send($c['email'], "Reset your $site portal password", $text, $html, null, 'client_reset');
}

function portal_forgot(): string
{
    $sent = false;
    $error = null;
    if (is_post()) {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
            $error = 'Your session expired. Please try again.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email.';
        } elseif (!Security::rateLimit('client_forgot_ip', 6, 3600)) {
            $error = 'Too many requests. Please wait an hour and try again.';
        } else {
            $c = Database::one('SELECT id, contact_name, email FROM clients WHERE email = ? AND is_active = 1', [$email]);
            if ($c && Security::rateLimit('client_forgot', 3, 3600, (string) $c['id'])) {
                portal_send_link($c, false, true);
            }
            $sent = true;
        }
    }
    return portal_view('forgot', ['sent' => $sent, 'error' => $error, 'pageTitle' => 'Forgot password', 'bare' => true]);
}

function portal_reset(string $token): string
{
    $id = Auth::findResetToken('client', $token);
    $c = $id ? Database::one('SELECT id, email FROM clients WHERE id = ? AND is_active = 1', [$id]) : null;
    $errors = [];
    if ($c && is_post()) {
        $new = (string) ($_POST['new_password'] ?? '');
        if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
            $errors[] = 'Your session expired. Please try again.';
        } elseif ($new !== (string) ($_POST['confirm_password'] ?? '')) {
            $errors[] = 'The two passwords do not match.';
        } elseif ($p = Auth::passwordProblem($new)) {
            $errors[] = $p;
        }
        if (!$errors) {
            Database::run('UPDATE clients SET password_hash = ?, must_change_password = 0 WHERE id = ?', [Auth::hash($new), $c['id']]);
            Auth::clearResetTokens('client', (int) $c['id']);
            Auth::clearLoginLock($c['email'], 'client_');
            flash('error', 'Your password is saved. Please sign in.');
            redirect(url('portal/login'));
        }
    }
    return portal_view('reset', ['valid' => (bool) $c, 'errors' => $errors, 'pageTitle' => 'Choose a password', 'bare' => true]);
}

function portal_home(): string
{
    $c = Portal::require();
    $projects = Database::all('SELECT p.*, u.name AS manager_name,
        (SELECT COUNT(*) FROM project_messages m WHERE m.project_id = p.id AND m.sender_type = \'staff\' AND m.read_by_client = 0) AS unread,
        (SELECT MAX(created_at) FROM project_updates pu WHERE pu.project_id = p.id) AS last_update
        FROM client_projects p LEFT JOIN users u ON u.id = p.manager_id WHERE p.client_id = ? ORDER BY FIELD(p.status, \'in_progress\', \'review\', \'planning\', \'on_hold\', \'completed\', \'cancelled\'), p.updated_at DESC', [$c['id']]);
    foreach ($projects as &$p) {
        $p['totals'] = Portal::totals($p);
    }
    unset($p);
    return portal_view('home', ['projects' => $projects, 'pageTitle' => 'Your projects']);
}

function portal_project(string $id): string
{
    $c = Portal::require();
    $p = Portal::project((int) $c['id'], $id) ?? abort(404);
    Database::run("UPDATE project_messages SET read_by_client = 1 WHERE project_id = ? AND sender_type = 'staff' AND read_by_client = 0", [$p['id']]);
    return portal_view('project', [
        'p' => $p,
        'totals' => Portal::totals($p),
        'updates' => Database::all('SELECT pu.title, pu.body, pu.progress, pu.created_at, u.name FROM project_updates pu LEFT JOIN users u ON u.id = pu.user_id WHERE pu.project_id = ? ORDER BY pu.id DESC', [$p['id']]),
        'entries' => Database::all('SELECT type, amount, description, reference, method, entry_date, due_date FROM project_transactions WHERE project_id = ? ORDER BY entry_date DESC, id DESC', [$p['id']]),
        'pageTitle' => $p['title'],
    ]);
}

/** JSON: new chat messages after ?after=ID. */
function portal_messages(string $id): never
{
    $c = Portal::client();
    $p = $c && (int) $c['must_change_password'] === 0 ? Portal::project((int) $c['id'], $id) : null;
    if (!$p) {
        json_response(['ok' => false], 404);
    }
    $after = max(0, (int) ($_GET['after'] ?? 0));
    $list = Portal::messages((int) $p['id'], $after, 'client', url('portal/files/'));
    if ($list) {
        Database::run("UPDATE project_messages SET read_by_client = 1 WHERE project_id = ? AND sender_type = 'staff' AND read_by_client = 0", [$p['id']]);
    }
    json_response(['ok' => true, 'messages' => $list]);
}

function portal_send_message(string $id): never
{
    $c = Portal::client();
    $p = $c ? Portal::project((int) $c['id'], $id) : null;
    if (!$p || (int) $c['must_change_password'] === 1) {
        json_response(['ok' => false, 'message' => 'Please sign in again.'], 403);
    }
    if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
        json_response(['ok' => false, 'message' => 'Your session expired. Reload the page and try again.'], 419);
    }
    if (!Security::rateLimit('portal_msg', 30, 600, (string) $c['id'])) {
        json_response(['ok' => false, 'message' => 'You are sending messages very quickly. Please wait a minute.'], 429);
    }
    $body = trim(str_replace("\r\n", "\n", (string) ($_POST['body'] ?? '')));
    $hasFile = ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if (($body === '' && !$hasFile) || mb_strlen($body) > 5000) {
        json_response(['ok' => false, 'message' => $body === '' ? 'Write a message or attach a file.' : 'Messages can be up to 5,000 characters.'], 422);
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
        'project_id' => $p['id'], 'sender_type' => 'client', 'sender_id' => $c['id'], 'sender_name' => $c['contact_name'],
        'body' => $body, 'file_stored' => $file[0], 'file_name' => $file[1], 'file_size' => $file[2], 'read_by_client' => 1,
    ]);
    $to = $p['manager_email'] ?: implode(',', Mailer::alertList('clients'));
    Portal::notify($to, 'staff', (int) $p['id'], 'New message from ' . $c['contact_name'] . ': ' . $p['title'], 'New client message',
        $c['contact_name'] . ($c['company_name'] !== '' ? ' (' . $c['company_name'] . ')' : '') . ' sent a message about "' . $p['title'] . '". Open the project to read and reply.',
        'Open the project', absolute_url('admin/projects/' . $p['id']) . '#chat');
    json_response(['ok' => true]);
}

function portal_file(string $id): never
{
    $c = Portal::require();
    $m = ctype_digit($id) ? Database::one('SELECT m.file_stored, m.file_name FROM project_messages m JOIN client_projects p ON p.id = m.project_id WHERE m.id = ? AND p.client_id = ?', [(int) $id, $c['id']]) : null;
    $m ? Portal::sendFile($m) : abort(404);
}

function portal_account(): string
{
    $c = Portal::require(true);
    $forced = (int) $c['must_change_password'] === 1;
    $errors = [];
    if (is_post()) {
        portal_csrf('portal/account');
        $row = Database::one('SELECT password_hash FROM clients WHERE id = ?', [$c['id']]);
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
            Database::run('UPDATE clients SET password_hash = ?, must_change_password = 0 WHERE id = ?', [Auth::hash($new), $c['id']]);
            Auth::clearResetTokens('client', (int) $c['id']);
            Portal::refreshSession((int) $c['id']);
            flash('success', 'Password changed.');
            redirect(url($forced ? 'portal' : 'portal/account'));
        }
    }
    return portal_view('account', ['errors' => $errors, 'forced' => $forced, 'pageTitle' => 'Account']);
}

/** @var Router $router */
$router->any('/portal/login', fn () => portal_login());
$router->post('/portal/logout', fn () => portal_logout());
$router->any('/portal/forgot', fn () => portal_forgot());
$router->any('/portal/reset/{token}', fn (string $token) => portal_reset($token));
$router->get('/portal', fn () => portal_home());
$router->get('/portal/projects/{id}', fn (string $id) => portal_project($id));
$router->get('/portal/projects/{id}/messages', fn (string $id) => portal_messages($id));
$router->post('/portal/projects/{id}/messages', fn (string $id) => portal_send_message($id));
$router->get('/portal/files/{id}', fn (string $id) => portal_file($id));
$router->any('/portal/account', fn () => portal_account());
