<?php
declare(strict_types=1);

/*
 * Messages and notices between the company (HR and admins) and each team member.
 * Every message is saved here and also emailed from the HR mailbox. Replies made in the portal are saved
 * and emailed to HR, so the conversation lives in both places.
 */

const MESSAGE_KINDS = ['message' => 'Message', 'notice' => 'Notice (announcement)'];

/** The address we can email a person at: their sign-in email, else work, else personal. */
function msg_address(array $p): string
{
    foreach ([$p['login_email'] ?? '', $p['work_email'] ?? '', $p['personal_email'] ?? ''] as $a) {
        if (filter_var($a, FILTER_VALIDATE_EMAIL)) {
            return $a;
        }
    }
    return '';
}

/** Unread replies for HR, or unread messages for one person. */
function msg_unread(?int $employeeId = null): int
{
    if ($employeeId === null) {
        return (int) Database::value("SELECT COUNT(*) FROM staff_messages WHERE direction = 'in' AND read_at IS NULL");
    }
    return (int) Database::value("SELECT COUNT(*) FROM staff_messages WHERE employee_id = ? AND direction = 'out' AND read_at IS NULL", [$employeeId]);
}

function msg_me(array $user): ?array
{
    return Database::one('SELECT * FROM employees WHERE user_id = ?', [$user['id']]);
}

/** Emails one message to its person from the HR mailbox, with a link to answer in the portal. */
function msg_email(array $person, array $m, bool $hasLogin): void
{
    $to = msg_address($person);
    if ($to === '') {
        return;
    }
    $site = setting('site_name', 'AivraSol');
    $first = strtok(Emails::safeName($person['full_name']), ' ') ?: 'there';
    $root = (int) ($m['thread_id'] ?: $m['id']);
    $label = $m['kind'] === 'notice' ? 'Notice' : 'Message';
    $note = $hasLogin
        ? "Please reply from your portal so your answer is saved with this conversation. Your reply reaches us by email too."
        : "You can reply to this email.";
    [$text, $html] = Emails::notice($m['subject'], "Dear $first,\n\n" . $m['body'] . "\n\n" . $note, $hasLogin ? 'Read and reply in the portal' : null, $hasLogin ? absolute_url('admin/inbox/' . $root) : null, "$label from the $site team.");
    Mailer::queue($to, ($m['kind'] === 'notice' ? 'Notice: ' : '') . $m['subject'] . " ($site)", $text, $html, null, 'staff_message');
    Database::run('UPDATE staff_messages SET emailed = 1 WHERE id = ?', [$m['id']]);
}

function msg_people(array $ids): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (!$ids) {
        return [];
    }
    return Database::all('SELECT e.id, e.full_name, e.designation, e.work_email, e.personal_email, e.status, e.user_id, u.email AS login_email, u.is_active
        FROM employees e LEFT JOIN users u ON u.id = e.user_id WHERE e.id IN (' . implode(',', array_fill(0, count($ids), '?')) . ') ORDER BY e.full_name', $ids);
}

/** HR inbox: every conversation, newest activity first. */
function admin_messages(): string
{
    Auth::require('employees');
    $show = ($_GET['show'] ?? '') === 'unread' ? 'unread' : '';
    $filter = $show === 'unread' ? "AND EXISTS (SELECT 1 FROM staff_messages x WHERE x.thread_id = r.id AND x.direction = 'in' AND x.read_at IS NULL)" : '';
    [$page, $per, $offset] = admin_page(25);
    $total = (int) Database::value("SELECT COUNT(*) FROM staff_messages r WHERE r.thread_id IS NULL $filter");
    $rows = Database::all("SELECT r.id, r.subject, r.kind, r.created_at, e.id AS employee_id, e.full_name, e.designation,
            (SELECT MAX(x.created_at) FROM staff_messages x WHERE x.id = r.id OR x.thread_id = r.id) AS last_at,
            (SELECT COUNT(*) FROM staff_messages x WHERE x.thread_id = r.id) AS replies,
            (SELECT COUNT(*) FROM staff_messages x WHERE (x.id = r.id OR x.thread_id = r.id) AND x.direction = 'in' AND x.read_at IS NULL) AS unread,
            (SELECT COUNT(*) FROM staff_messages x WHERE (x.id = r.id OR x.thread_id = r.id) AND x.direction = 'out' AND x.read_at IS NULL) AS unseen
        FROM staff_messages r JOIN employees e ON e.id = r.employee_id
        WHERE r.thread_id IS NULL $filter
        ORDER BY last_at DESC LIMIT $per OFFSET $offset");
    return admin_view('messages', ['rows' => $rows, 'show' => $show, 'total' => $total, 'page' => $page, 'per' => $per, 'unread' => msg_unread(), 'pageTitle' => 'Messages to the team']);
}

/** Write to one person, a selection, or a whole group. */
function admin_message_new(): string
{
    $user = Auth::require('employees');
    $errors = [];
    $ids = array_map('intval', (array) ($_POST['to'] ?? $_GET['to'] ?? []));
    $group = (string) ($_POST['group'] ?? $_GET['group'] ?? '');
    $values = ['kind' => 'message', 'subject' => '', 'body' => '', 'email' => '1'];
    if ($group !== '') {
        $where = ["e.status = 'active'", "e.approval = 'approved'"];
        $params = [];
        if (isset(Auth::ROLES[$group])) {
            $where[] = 'u.role = ?';
            $params[] = $group;
        } elseif ($group !== 'all') {
            $group = '';
        }
        if ($group !== '') {
            $ids = array_map('intval', array_column(Database::all('SELECT e.id FROM employees e LEFT JOIN users u ON u.id = e.user_id WHERE ' . implode(' AND ', $where), $params), 'id'));
        }
    }
    $people = msg_people($ids);
    if (is_post()) {
        admin_csrf();
        $values = [
            'kind' => isset(MESSAGE_KINDS[$_POST['kind'] ?? '']) ? $_POST['kind'] : 'message',
            'subject' => mb_substr(trim(str_replace(["\r", "\n"], ' ', (string) ($_POST['subject'] ?? ''))), 0, 150),
            'body' => mb_substr(trim(str_replace("\r\n", "\n", (string) ($_POST['body'] ?? ''))), 0, 8000),
            'email' => !empty($_POST['email']) ? '1' : '',
        ];
        if (!$people) $errors[] = 'Choose at least one person.';
        if (count($people) > 300) $errors[] = 'Send to 300 people at most at a time.';
        if ($values['subject'] === '') $errors[] = 'Add a subject.';
        if (mb_strlen($values['body']) < 5) $errors[] = 'Write the message.';
        if (!$errors && !Security::rateLimit('staff_message', 30, 3600, (string) $user['id'])) $errors[] = 'Too many messages in a short time. Please wait a little.';
        if (!$errors) {
            $sent = 0;
            $noEmail = [];
            foreach ($people as $p) {
                $id = Database::insert('staff_messages', ['employee_id' => $p['id'], 'direction' => 'out', 'kind' => $values['kind'], 'subject' => $values['subject'], 'body' => $values['body'], 'sender_id' => $user['id']]);
                $hasLogin = $p['user_id'] && (int) $p['is_active'] === 1;
                if ($values['email']) {
                    if (msg_address($p) === '') {
                        $noEmail[] = $p['full_name'];
                    } else {
                        msg_email($p, ['id' => $id, 'thread_id' => null, 'kind' => $values['kind'], 'subject' => $values['subject'], 'body' => $values['body']], $hasLogin);
                    }
                }
                $sent++;
            }
            Auth::audit((int) $user['id'], 'staff_message', $values['subject'] . " ($sent)");
            flash('success', ($values['kind'] === 'notice' ? 'Notice' : 'Message') . " sent to $sent " . ($sent === 1 ? 'person' : 'people') . ($values['email'] ? ' by email and in their portal.' : ' in their portal.') . ($noEmail ? ' No email address for: ' . implode(', ', $noEmail) . '.' : ''));
            redirect(url($sent === 1 ? 'admin/messages/' . $id : 'admin/messages'));
        }
    }
    $roleCounts = array_column(Database::all("SELECT u.role, COUNT(*) AS n FROM employees e JOIN users u ON u.id = e.user_id WHERE e.status = 'active' AND e.approval = 'approved' GROUP BY u.role"), 'n', 'role');
    return admin_view('message-new', ['people' => $people, 'group' => $group, 'values' => $values, 'errors' => $errors, 'roleCounts' => $roleCounts, 'pageTitle' => 'New message']);
}

/** One conversation. $mine = the signed-in person's own inbox; otherwise HR's view. */
function msg_thread(string $id, bool $mine): string
{
    $user = Auth::require($mine ? 'profile' : 'employees');
    $root = ctype_digit($id) ? Database::one('SELECT * FROM staff_messages WHERE id = ? AND thread_id IS NULL', [(int) $id]) : null;
    $me = $mine ? msg_me($user) : null;
    if (!$root || ($mine && (!$me || (int) $root['employee_id'] !== (int) $me['id']))) {
        abort(404);
    }
    $person = msg_people([(int) $root['employee_id']])[0] ?? abort(404);
    $base = $mine ? 'admin/inbox/' : 'admin/messages/';
    $errors = [];
    if (is_post()) {
        admin_csrf();
        $body = mb_substr(trim(str_replace("\r\n", "\n", (string) ($_POST['body'] ?? ''))), 0, 8000);
        if (mb_strlen($body) < 2) {
            $errors[] = 'Write your reply.';
        } elseif (!Security::rateLimit('staff_reply', 40, 3600, (string) $user['id'])) {
            $errors[] = 'Too many replies in a short time. Please wait a little.';
        } else {
            $subject = 'Re: ' . preg_replace('/^(Re: )+/i', '', $root['subject']);
            $mid = Database::insert('staff_messages', ['thread_id' => $root['id'], 'employee_id' => $root['employee_id'], 'direction' => $mine ? 'in' : 'out', 'kind' => 'message', 'subject' => $subject, 'body' => $body, 'sender_id' => $user['id']]);
            if ($mine) {
                // The person's reply also goes to HR by email.
                $hr = Mailer::alertList('hr');
                if ($hr) {
                    [$text, $html] = Emails::notice('Reply from ' . Emails::safeName($person['full_name']), $body, 'Open the conversation', absolute_url('admin/messages/' . $root['id']), 'Subject: ' . $root['subject']);
                    Mailer::queue($hr, $subject . ' (from ' . $person['full_name'] . ')', $text, $html, msg_address($person) ?: null, 'staff_reply');
                }
                flash('success', 'Reply sent. HR sees it here and by email.');
            } else {
                msg_email($person, ['id' => $mid, 'thread_id' => $root['id'], 'kind' => 'message', 'subject' => $subject, 'body' => $body], $person['user_id'] && (int) $person['is_active'] === 1);
                flash('success', 'Reply sent to ' . $person['full_name'] . '.');
            }
            redirect(url($base . $root['id']) . '#last');
        }
    }
    // Opening the conversation marks the other side's messages as read.
    Database::run('UPDATE staff_messages SET read_at = UTC_TIMESTAMP() WHERE (id = ? OR thread_id = ?) AND direction = ? AND read_at IS NULL', [$root['id'], $root['id'], $mine ? 'out' : 'in']);
    $items = Database::all('SELECT m.*, u.name AS sender FROM staff_messages m LEFT JOIN users u ON u.id = m.sender_id WHERE m.id = ? OR m.thread_id = ? ORDER BY m.id', [$root['id'], $root['id']]);
    return admin_view('message-thread', ['root' => $root, 'items' => $items, 'person' => $person, 'mine' => $mine, 'errors' => $errors, 'pageTitle' => $root['subject']]);
}

/** A team member's own messages and notices. */
function admin_inbox(): string
{
    $user = Auth::require('profile');
    $me = msg_me($user);
    [$page, $per, $offset] = admin_page(25);
    $total = $me ? (int) Database::value('SELECT COUNT(*) FROM staff_messages WHERE thread_id IS NULL AND employee_id = ?', [$me['id']]) : 0;
    $rows = $me ? Database::all("SELECT r.id, r.subject, r.kind, r.created_at,
            (SELECT MAX(x.created_at) FROM staff_messages x WHERE x.id = r.id OR x.thread_id = r.id) AS last_at,
            (SELECT COUNT(*) FROM staff_messages x WHERE x.thread_id = r.id) AS replies,
            (SELECT COUNT(*) FROM staff_messages x WHERE (x.id = r.id OR x.thread_id = r.id) AND x.direction = 'out' AND x.read_at IS NULL) AS unread
        FROM staff_messages r WHERE r.thread_id IS NULL AND r.employee_id = ? ORDER BY last_at DESC LIMIT $per OFFSET $offset", [$me['id']]) : [];
    return admin_view('inbox', ['rows' => $rows, 'total' => $total, 'page' => $page, 'per' => $per, 'pageTitle' => 'My messages']);
}
