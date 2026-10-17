<?php
declare(strict_types=1);

/*
 * Issuance: certificates and letters for the team, from ready-made or custom templates.
 * Every document gets a reference number, a QR code that proves it is genuine, and can be withdrawn later.
 */

const DOC_KINDS = ['certificate' => 'Certificate (landscape, framed)', 'letter' => 'Letter (A4 page with letterhead)'];

function issuance_templates(bool $activeOnly = true): array
{
    return Database::all('SELECT * FROM doc_templates' . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY sort_order, id');
}

/** Register of everything issued. */
function admin_issuance(): string
{
    Auth::require('employees');
    $f = ['q' => trim((string) ($_GET['q'] ?? '')), 'type' => (string) ($_GET['type'] ?? ''), 'state' => (string) ($_GET['state'] ?? '')];
    $where = [];
    $params = [];
    if ($f['q'] !== '' && mb_strlen($f['q']) <= 100) {
        $like = '%' . addcslashes($f['q'], '%_\\') . '%';
        $where[] = '(d.ref_no LIKE ? OR d.title LIKE ? OR e.full_name LIKE ? OR e.employee_code LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    }
    if (preg_match('/^[a-z]{2,12}$/', $f['type'])) {
        $where[] = 'd.doc_type = ?';
        $params[] = $f['type'];
    }
    if ($f['state'] === 'valid') {
        $where[] = 'd.revoked_at IS NULL';
    } elseif ($f['state'] === 'withdrawn') {
        $where[] = 'd.revoked_at IS NOT NULL';
    }
    $rows = Database::all('SELECT d.id, d.doc_type, d.ref_no, d.title, d.issued_at, d.revoked_at, e.id AS employee_id, e.full_name, e.employee_code, u.name AS issuer
        FROM employee_documents d JOIN employees e ON e.id = d.employee_id LEFT JOIN users u ON u.id = d.issued_by '
        . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY d.id DESC LIMIT 500', $params);
    $types = Database::all('SELECT doc_type, COUNT(*) AS n FROM employee_documents GROUP BY doc_type ORDER BY doc_type');
    return admin_view('issuance', ['rows' => $rows, 'f' => $f, 'types' => $types, 'templates' => issuance_templates(), 'pageTitle' => 'Issuance: certificates and letters']);
}

function issuance_doc_type(array $tpl): string
{
    return $tpl['code'] === 'EXP' ? 'experience' : strtolower($tpl['code']);
}

/** Step 1: person and template. Step 2: read and adjust the text. Step 3: issue. */
function admin_issuance_new(): string
{
    $user = Auth::require('employees');
    $people = Database::all("SELECT id, full_name, employee_code, designation, status, join_date, end_date FROM employees WHERE approval = 'approved' ORDER BY status = 'active' DESC, full_name");
    $templates = issuance_templates();
    $in = is_post() ? $_POST : $_GET;
    $empId = (int) ($in['employee'] ?? 0);
    $tplId = (int) ($in['template'] ?? 0);
    if (!$tplId && ($code = strtoupper((string) ($in['code'] ?? ''))) !== '') {
        foreach ($templates as $t) {
            if ($t['code'] === $code) {
                $tplId = (int) $t['id'];
            }
        }
    }
    $emp = $empId ? Database::one("SELECT * FROM employees WHERE id = ? AND approval = 'approved'", [$empId]) : null;
    $tpl = null;
    foreach ($templates as $t) {
        if ((int) $t['id'] === $tplId) {
            $tpl = $t;
        }
    }
    $errors = [];
    $end = (string) ($in['end_date'] ?? ($emp['end_date'] ?? gmdate('Y-m-d')));
    $details = mb_substr(trim((string) ($in['details'] ?? '')), 0, 200);
    $step = 1;
    $title = '';
    $body = '';
    $notify = ($_POST['action'] ?? '') === 'issue' ? !empty($_POST['notify']) : true;
    if (is_post()) {
        admin_csrf();
        if (!$emp) $errors[] = 'Choose a person.';
        if (!$tpl) $errors[] = 'Choose what to issue.';
        if ($emp && $tpl && $tpl['needs_end_date']) {
            $d = DateTime::createFromFormat('!Y-m-d', $end);
            if (!$d || $d->format('Y-m-d') !== $end || $end < $emp['join_date']) {
                $errors[] = 'Choose a valid end date on or after the joining date (' . Documents::longDate($emp['join_date']) . ').';
            }
        }
        if ($tpl && $tpl['details_label'] !== '' && $details === '') {
            $errors[] = 'Fill in: ' . $tpl['details_label'] . '.';
        }
        if (!$errors) {
            $step = 2;
            $endForText = $tpl['needs_end_date'] ? $end : ($emp['end_date'] ?? gmdate('Y-m-d'));
            $title = ($_POST['action'] ?? '') === 'issue' ? mb_substr(trim((string) ($_POST['title'] ?? '')), 0, 60) : $tpl['title'];
            $body = ($_POST['action'] ?? '') === 'issue'
                ? trim(str_replace("\r\n", "\n", (string) ($_POST['body'] ?? '')))
                : trim(preg_replace('/ {2,}/', ' ', strtr($tpl['body'], Documents::placeholders($emp, $endForText, $details))) ?? '');
            if (($_POST['action'] ?? '') === 'issue') {
                if ($title === '') $errors[] = 'Give it a title.';
                if (mb_strlen($body) < 20 || mb_strlen($body) > 6000) $errors[] = 'The text must be between 20 and 6,000 characters.';
                if (!$errors) {
                    $ref = Documents::nextRef($tpl['code']);
                    $docId = Database::insert('employee_documents', [
                        'employee_id' => $emp['id'],
                        'doc_type' => issuance_doc_type($tpl),
                        'ref_no' => $ref,
                        'verify_token' => Documents::token(),
                        'title' => $title,
                        'data' => json_encode(Documents::letterSnapshot($emp, $endForText, $body, ['kind' => $tpl['kind'], 'salutation' => $tpl['salutation'], 'template' => $tpl['code']]), JSON_UNESCAPED_UNICODE),
                        'issued_by' => $user['id'],
                        'issued_at' => gmdate('Y-m-d'),
                    ]);
                    // An experience certificate means the person has left: record the last working day.
                    if ($tpl['code'] === 'EXP' && !$emp['end_date']) {
                        Database::run("UPDATE employees SET end_date = ?, status = IF(status = 'active', 'resigned', status) WHERE id = ?", [$end, $emp['id']]);
                    }
                    Auth::audit((int) $user['id'], 'document_issue', $ref);
                    if ($notify) {
                        require_once APP_PATH . '/admin/messages.php';
                        $p = msg_people([(int) $emp['id']])[0] ?? null;
                        if ($p) {
                            $mid = Database::insert('staff_messages', ['employee_id' => $emp['id'], 'direction' => 'out', 'kind' => 'notice', 'subject' => "$title issued", 'sender_id' => $user['id'],
                                'body' => "Your $title (reference $ref) has been issued. You can download it any time from My profile in the portal, under My documents."]);
                            msg_email($p, ['id' => $mid, 'thread_id' => null, 'kind' => 'notice', 'subject' => "$title issued", 'body' => "Your $title (reference $ref) has been issued. You can download it any time from My profile in the portal, under My documents."], $p['user_id'] && (int) $p['is_active'] === 1);
                        }
                    }
                    flash('success', "$title $ref issued to {$emp['full_name']}." . ($notify ? ' They were told by email and in their messages.' : ''));
                    redirect(url('admin/issuance') . '?issued=' . $docId);
                }
            }
        }
    }
    return admin_view('issuance-new', [
        'people' => $people, 'templates' => $templates, 'emp' => $emp, 'tpl' => $tpl, 'end' => $end, 'details' => $details,
        'step' => $step, 'title' => $title, 'body' => $body, 'errors' => $errors, 'notify' => $notify,
        'ready' => setting('doc_signatory_name') !== '' && setting('doc_signature') !== '',
        'pageTitle' => 'Issue a certificate or letter',
    ]);
}

/** Template library: edit the ready-made ones or add your own. */
function admin_issuance_templates(): string
{
    $user = Auth::require('employees');
    if (is_post()) {
        admin_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        $t = $id ? Database::one('SELECT * FROM doc_templates WHERE id = ?', [$id]) : null;
        $action = (string) ($_POST['action'] ?? '');
        if ($t && $action === 'toggle') {
            Database::run('UPDATE doc_templates SET is_active = 1 - is_active WHERE id = ?', [$id]);
            flash('success', $t['name'] . ($t['is_active'] ? ' is hidden from the issue form.' : ' is available again.'));
        } elseif ($t && $action === 'delete' && !$t['is_builtin']) {
            Database::run('DELETE FROM doc_templates WHERE id = ?', [$id]);
            flash('success', 'Template deleted. Documents already issued with it are not affected.');
        } elseif ($t && $action === 'restore' && $t['is_builtin']) {
            foreach (require ROOT_PATH . '/database/data/doc_templates.php' as $d) {
                if ($d['code'] === $t['code']) {
                    Database::run('UPDATE doc_templates SET name = ?, kind = ?, title = ?, salutation = ?, body = ?, needs_end_date = ?, details_label = ? WHERE id = ?', [$d['name'], $d['kind'], $d['title'], $d['salutation'], $d['body'], $d['needs_end_date'], $d['details_label'], $id]);
                }
            }
            flash('success', $t['name'] . ' is back to the original text.');
        }
        Auth::audit((int) $user['id'], 'doc_template_' . $action, (string) ($t['code'] ?? ''));
        redirect(url('admin/issuance/templates'));
    }
    return admin_view('issuance-templates', ['templates' => issuance_templates(false), 'pageTitle' => 'Certificate and letter templates']);
}

function admin_issuance_template_edit(?string $id = null): string
{
    $user = Auth::require('employees');
    $t = $id !== null ? (ctype_digit($id) ? Database::one('SELECT * FROM doc_templates WHERE id = ?', [(int) $id]) : null) ?? abort(404) : null;
    $values = $t ?? ['name' => '', 'code' => '', 'kind' => 'certificate', 'title' => '', 'salutation' => 'This certificate is presented to', 'body' => '', 'needs_end_date' => 0, 'details_label' => '', 'sort_order' => 50];
    $errors = [];
    if (is_post()) {
        admin_csrf();
        $values = [
            'name' => mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 80),
            'code' => strtoupper(preg_replace('/[^A-Za-z]/', '', (string) ($_POST['code'] ?? '')) ?? ''),
            'kind' => isset(DOC_KINDS[$_POST['kind'] ?? '']) ? $_POST['kind'] : 'certificate',
            'title' => mb_substr(trim((string) ($_POST['title'] ?? '')), 0, 60),
            'salutation' => mb_substr(trim((string) ($_POST['salutation'] ?? '')), 0, 120),
            'body' => mb_substr(trim(str_replace("\r\n", "\n", (string) ($_POST['body'] ?? ''))), 0, 6000),
            'needs_end_date' => !empty($_POST['needs_end_date']) ? 1 : 0,
            'details_label' => mb_substr(trim((string) ($_POST['details_label'] ?? '')), 0, 120),
            'sort_order' => (int) ($_POST['sort_order'] ?? 50),
        ];
        if ($t && $t['is_builtin']) {
            $values['code'] = $t['code'];
        }
        if ($values['name'] === '') $errors['name'] = 'Give the template a name.';
        if (strlen($values['code']) < 2 || strlen($values['code']) > 6) $errors['code'] = 'Use 2 to 6 letters, for example PRO.';
        elseif (Database::value('SELECT COUNT(*) FROM doc_templates WHERE code = ? AND id <> ?', [$values['code'], (int) ($t['id'] ?? 0)])) $errors['code'] = 'Another template uses this code.';
        if ($values['title'] === '') $errors['title'] = 'Add the title printed on the document.';
        if (mb_strlen($values['body']) < 20) $errors['body'] = 'Write the text (20 characters or more).';
        if (str_contains($values['body'], '{details}') && $values['details_label'] === '') $errors['details_label'] = 'The text uses {details}, so say what to fill in.';
        if (!$errors) {
            if ($t) {
                Database::update('doc_templates', (int) $t['id'], $values);
            } else {
                Database::insert('doc_templates', $values);
            }
            Auth::audit((int) $user['id'], 'doc_template_save', $values['code']);
            flash('success', 'Template saved.');
            redirect(url('admin/issuance/templates'));
        }
    }
    return admin_view('issuance-template-edit', ['t' => $t, 'values' => $values, 'errors' => $errors, 'pageTitle' => $t ? 'Edit ' . $t['name'] : 'New template']);
}
