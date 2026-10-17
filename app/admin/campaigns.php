<?php
declare(strict_types=1);

require_once APP_PATH . '/lib/Campaigns.php';

/* ---------- Contacts ---------- */

function admin_contacts(): string
{
    $user = Auth::require('campaigns');
    if (is_post()) {
        admin_csrf();
        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flash('error', 'Enter a valid email.');
            } else {
                $n = admin_contact_upsert([
                    'email' => $email,
                    'first_name' => (string) ($_POST['first_name'] ?? ''),
                    'last_name' => (string) ($_POST['last_name'] ?? ''),
                    'company' => (string) ($_POST['company'] ?? ''),
                    'website' => (string) ($_POST['website'] ?? ''),
                    'country' => (string) ($_POST['country'] ?? ''),
                    'tags' => (string) ($_POST['tags'] ?? ''),
                ], 'manual');
                flash('success', $n ? 'Contact added.' : 'That contact already exists; their details were updated.');
            }
        } elseif ($action === 'import') {
            flash(...admin_contacts_import($user));
        } elseif ($action === 'leads') {
            $added = 0;
            foreach (Database::all("SELECT name, email, company FROM leads WHERE status <> 'spam'") as $l) {
                $parts = preg_split('/\s+/', trim($l['name']), 2) ?: [''];
                $added += admin_contact_upsert(['email' => $l['email'], 'first_name' => $parts[0], 'last_name' => $parts[1] ?? '', 'company' => (string) $l['company'], 'tags' => 'website-lead'], 'lead', false);
            }
            Auth::audit((int) $user['id'], 'contacts_from_leads', (string) $added);
            flash('success', "$added website leads added as contacts with the tag website-lead.");
        } elseif (in_array($action, ['unsubscribe', 'resubscribe', 'delete'], true) && ctype_digit((string) ($_POST['id'] ?? ''))) {
            $row = Database::one('SELECT id, email, status FROM contacts WHERE id = ?', [(int) $_POST['id']]);
            if (!$row) {
                flash('error', 'Contact not found.');
            } elseif ($action === 'resubscribe') {
                // Only an admin can undo an opt-out, and only when the person has asked to be added back.
                if ($row['status'] !== 'unsubscribed' || !Auth::can('settings', $user)) {
                    flash('error', 'Only an admin can re-subscribe someone, and bounced or complained addresses can never be re-subscribed.');
                } else {
                    Database::run("UPDATE contacts SET status = 'subscribed' WHERE id = ?", [$row['id']]);
                    Database::run('DELETE FROM email_suppressions WHERE email_hash = ?', [Campaigns::suppressionHash($row['email'])]);
                    Auth::audit((int) $user['id'], 'contact_resubscribe', $row['email']);
                    flash('success', 'Contact re-subscribed. Only do this when they asked for it.');
                }
            } else {
                if ($action === 'delete') {
                    if ($row['status'] !== 'subscribed') {
                        Campaigns::suppress($row['email'], $row['status']);
                    }
                    Database::run('DELETE FROM contacts WHERE id = ?', [$row['id']]);
                } else {
                    Database::run("UPDATE contacts SET status = 'unsubscribed' WHERE id = ?", [$row['id']]);
                    Campaigns::suppress($row['email'], 'unsubscribed');
                }
                Auth::audit((int) $user['id'], 'contact_' . $action, $row['email']);
                flash('success', $action === 'delete' ? 'Contact deleted.' : 'Contact unsubscribed.');
            }
        }
        redirect(url('admin/contacts') . (isset($_POST['back']) && is_string($_POST['back']) && str_starts_with($_POST['back'], '?') ? $_POST['back'] : ''));
    }
    $status = isset(Campaigns::CONTACT_STATUSES[$_GET['status'] ?? '']) ? $_GET['status'] : '';
    $tag = is_string($_GET['tag'] ?? null) ? Campaigns::cleanTags($_GET['tag']) : '';
    $q = trim((string) ($_GET['q'] ?? ''));
    $where = [];
    $params = [];
    if ($status !== '') { $where[] = 'status = ?'; $params[] = $status; }
    if ($tag !== '') { $where[] = 'FIND_IN_SET(?, tags) > 0'; $params[] = $tag; }
    if ($q !== '' && mb_strlen($q) <= 100) {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $where[] = '(email LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR company LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    } else {
        $q = '';
    }
    $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $per = 50;
    $total = (int) Database::value("SELECT COUNT(*) FROM contacts $w", $params);
    $rows = Database::all("SELECT * FROM contacts $w ORDER BY id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $params);
    $counts = array_column(Database::all('SELECT status, COUNT(*) AS n FROM contacts GROUP BY status'), 'n', 'status');
    return admin_view('contacts', compact('rows', 'total', 'page', 'per', 'status', 'tag', 'q', 'counts') + ['tags' => Campaigns::tags(), 'pageTitle' => 'Contacts']);
}

/** Adds or updates a contact. Never re-subscribes someone who unsubscribed or bounced. Returns 1 when added. */
function admin_contact_upsert(array $d, string $source, bool $overwrite = true): int
{
    $clean = fn ($v, $max) => mb_substr(trim(preg_replace('/[\x00-\x1F]/', '', (string) $v) ?? ''), 0, $max);
    $row = [
        'email' => strtolower($clean($d['email'], 190)),
        'first_name' => $clean($d['first_name'] ?? '', 80),
        'last_name' => $clean($d['last_name'] ?? '', 80),
        'company' => $clean($d['company'] ?? '', 120),
        'website' => $clean($d['website'] ?? '', 190),
        'country' => $clean($d['country'] ?? '', 60),
        'tags' => Campaigns::cleanTags((string) ($d['tags'] ?? '')),
    ];
    $existing = Database::one('SELECT id, tags FROM contacts WHERE email = ?', [$row['email']]);
    if ($existing) {
        $tags = Campaigns::cleanTags($existing['tags'] . ',' . $row['tags']);
        if ($overwrite) {
            $upd = array_filter(['first_name' => $row['first_name'], 'last_name' => $row['last_name'], 'company' => $row['company'], 'website' => $row['website'], 'country' => $row['country']], fn ($v) => $v !== '');
            Database::update('contacts', (int) $existing['id'], $upd + ['tags' => $tags]);
        } else {
            Database::update('contacts', (int) $existing['id'], ['tags' => $tags]);
        }
        return 0;
    }
    // Someone who opted out or bounced before (even if since deleted) comes back as unsubscribed, never subscribed.
    $status = Campaigns::isSuppressed($row['email']) ? 'unsubscribed' : 'subscribed';
    Database::insert('contacts', $row + ['source' => $source, 'status' => $status, 'token' => bin2hex(random_bytes(16))]);
    return 1;
}

function admin_contacts_import(array $user): array
{
    if (($_POST['consent'] ?? '') !== '1') {
        return ['error', 'Please confirm you have a lawful reason to email these people.'];
    }
    $file = $_FILES['csv'] ?? null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return ['error', 'Choose a CSV file to import.'];
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['error', 'The CSV must be 5 MB or smaller.'];
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
    if (!in_array($mime, ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'], true)) {
        return ['error', 'That does not look like a CSV file. In Excel or Google Sheets choose "Download as CSV".'];
    }
    $h = fopen($file['tmp_name'], 'r');
    $first = fgets($h) ?: '';
    $delim = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
    $header = array_map(fn ($c) => strtolower(trim(str_replace("\xEF\xBB\xBF", '', $c), " \"'")), str_getcsv($first, $delim));
    $aliases = ['email' => ['email', 'e-mail', 'email address'], 'first_name' => ['first_name', 'first name', 'firstname', 'name'], 'last_name' => ['last_name', 'last name', 'lastname', 'surname'], 'company' => ['company', 'company name', 'organisation', 'organization'], 'website' => ['website', 'url', 'domain'], 'country' => ['country'], 'tags' => ['tags', 'tag', 'list']];
    $map = [];
    foreach ($aliases as $field => $names) {
        foreach ($names as $n) {
            if (($i = array_search($n, $header, true)) !== false) {
                $map[$field] = $i;
                break;
            }
        }
    }
    if (!isset($map['email'])) {
        fclose($h);
        return ['error', 'The first row must have column names, including one called "email".'];
    }
    $extraTags = (string) ($_POST['import_tags'] ?? '');
    [$added, $updated, $invalid, $rowsRead] = [0, 0, 0, 0];
    while (($cols = fgetcsv($h, 0, $delim)) !== false && $rowsRead < 20000) {
        $rowsRead++;
        $get = fn ($f) => isset($map[$f]) ? (string) ($cols[$map[$f]] ?? '') : '';
        $email = strtolower(trim($get('email')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $invalid++;
            continue;
        }
        $first = $get('first_name');
        $last = $get('last_name');
        if (!isset($map['last_name']) && ($header[$map['first_name'] ?? -1] ?? '') === 'name') {
            [$first, $last] = array_pad(preg_split('/\s+/', trim($first), 2) ?: [''], 2, '');
        }
        $n = admin_contact_upsert(['email' => $email, 'first_name' => $first, 'last_name' => $last, 'company' => $get('company'), 'website' => $get('website'), 'country' => $get('country'), 'tags' => $get('tags') . ',' . $extraTags], 'import');
        $n ? $added++ : $updated++;
    }
    fclose($h);
    Auth::audit((int) $user['id'], 'contacts_import', "added $added, updated $updated");
    return ['success', "Import finished: $added added, $updated already existed (kept their status), $invalid rows skipped for invalid email."];
}

/* ---------- Campaigns ---------- */

function admin_campaigns(): string
{
    $user = Auth::require('campaigns');
    if (is_post()) {
        admin_csrf();
        if (($_POST['action'] ?? '') === 'limits') {
            Auth::require('settings');
            admin_campaign_save_limits();
            Auth::audit((int) $user['id'], 'campaign_limits');
            flash('success', 'Sending settings saved.');
        }
        redirect(url('admin/campaigns'));
    }
    [$page, $per, $offset] = admin_page(20);
    $total = (int) Database::value('SELECT COUNT(*) FROM campaigns');
    $rows = Database::all("SELECT k.*, u.name AS author,
        (SELECT COUNT(*) FROM campaign_recipients r WHERE r.campaign_id = k.id) AS total,
        (SELECT COUNT(*) FROM campaign_recipients r WHERE r.campaign_id = k.id AND r.status = 'sent') AS sent
        FROM campaigns k LEFT JOIN users u ON u.id = k.created_by ORDER BY k.id DESC LIMIT $per OFFSET $offset");
    [$allowed, $why] = Campaigns::allowance();
    return admin_view('campaigns', ['listTotal' => $total, 'page' => $page, 'per' => $per,
        'rows' => $rows, 'allowed' => $allowed, 'why' => $why, 'limits' => Campaigns::limits(),
        'sent24' => (int) Database::value("SELECT COUNT(*) FROM campaign_recipients WHERE status = 'sent' AND sent_at > UTC_TIMESTAMP() - INTERVAL 1 DAY"),
        'queued' => (int) Database::value("SELECT COUNT(*) FROM campaign_recipients r JOIN campaigns k ON k.id = r.campaign_id WHERE r.status = 'queued' AND k.status = 'sending'"),
        'pageTitle' => 'Email campaigns',
    ]);
}

function admin_campaign_save_limits(): void
{
    $num = fn ($k, $min, $max, $def) => (string) max($min, min($max, (int) ($_POST[$k] ?? $def)));
    $save = [
        'camp_per_hour' => $num('camp_per_hour', 1, 200, 30),
        'camp_per_day' => $num('camp_per_day', 1, 2000, 150),
        'camp_gap_seconds' => $num('camp_gap_seconds', 5, 600, 45),
        'camp_window_start' => $num('camp_window_start', 0, 23, 9),
        'camp_window_end' => $num('camp_window_end', 1, 24, 18),
        'camp_rest_days' => $num('camp_rest_days', 0, 30, 2),
        'camp_weekdays_only' => isset($_POST['camp_weekdays_only']) ? '1' : '0',
        'camp_timezone' => in_array($_POST['camp_timezone'] ?? '', Campaigns::TIMEZONES, true) ? $_POST['camp_timezone'] : 'UTC',
    ];
    if ((int) $save['camp_window_end'] <= (int) $save['camp_window_start']) {
        $save['camp_window_end'] = (string) min(24, (int) $save['camp_window_start'] + 1);
    }
    $stmt = Database::pdo()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    foreach ($save as $k => $v) {
        $stmt->execute([$k, $v]);
    }
}

function admin_campaign_edit(?string $id = null): string
{
    $user = Auth::require('campaigns');
    $c = null;
    if ($id !== null) {
        $c = ctype_digit($id) ? Database::one('SELECT * FROM campaigns WHERE id = ?', [(int) $id]) : null;
        $c ?? abort(404);
    }
    $templates = Database::all('SELECT id, name, kind, layout, subject, body FROM email_templates WHERE is_active = 1 ORDER BY sort_order, id');
    $values = $c ?? ['name' => '', 'subject' => '', 'body' => '', 'layout' => 'plain', 'from_name' => $user['name'], 'reply_to' => '', 'audience_tag' => ''];
    if (!$c && ctype_digit((string) ($_GET['template'] ?? ''))) {
        foreach ($templates as $t) {
            if ((int) $t['id'] === (int) $_GET['template']) {
                $values = array_merge($values, ['name' => $t['name'] . ' ' . gmdate('M Y'), 'subject' => $t['subject'], 'body' => $t['body'], 'layout' => $t['layout']]);
            }
        }
    }
    $errors = [];
    $editable = !$c || in_array($c['status'], ['draft', 'paused'], true);
    if (is_post()) {
        admin_csrf();
        $action = $_POST['action'] ?? 'save';
        if ($c && in_array($action, ['start', 'pause', 'resume', 'cancel', 'delete', 'test', 'send_batch', 'send_now'], true)) {
            $back = url('admin/campaigns/' . $c['id']);
            switch ($action) {
                case 'test':
                    $me = ['first_name' => strtok($user['name'], ' ') ?: '', 'last_name' => '', 'company' => 'Example Ltd', 'website' => 'example.com', 'country' => '', 'token' => str_repeat('0', 32), 'email' => $user['email']];
                    [$subject, $text, $html, $headers] = Campaigns::build($c, $me);
                    $err = Mailer::send($user['email'], '[Test] ' . $subject, $text, $html, $c['reply_to'] ?: null, 'campaign_test', ['profile' => 'campaign', 'from_name' => $c['from_name'], 'headers' => $headers]);
                    $err === null ? flash('success', 'Test sent to ' . $user['email'] . '. Check how it looks, and whether it landed in the inbox or spam.') : flash('error', 'The test failed: ' . $err);
                    break;
                case 'start':
                    if ($c['status'] !== 'draft') break;
                    $n = Campaigns::queue($c);
                    if ($n === 0) {
                        flash('error', 'No subscribed contacts match this audience.');
                        break;
                    }
                    Database::run("UPDATE campaigns SET status = 'sending', started_at = UTC_TIMESTAMP() WHERE id = ?", [$c['id']]);
                    Auth::audit((int) $user['id'], 'campaign_start', $c['name'] . " ($n)");
                    flash('success', "Campaign started for $n contacts. Emails go out slowly within your limits.");
                    break;
                case 'pause':
                    Database::run("UPDATE campaigns SET status = 'paused' WHERE id = ? AND status = 'sending'", [$c['id']]);
                    flash('success', 'Campaign paused.');
                    break;
                case 'resume':
                    Database::run("UPDATE campaigns SET status = 'sending' WHERE id = ? AND status = 'paused'", [$c['id']]);
                    Database::run("DELETE FROM settings WHERE setting_key = 'camp_alert'");
                    flash('success', 'Campaign resumed.');
                    break;
                case 'cancel':
                    Database::run("UPDATE campaigns SET status = 'cancelled', finished_at = UTC_TIMESTAMP() WHERE id = ? AND status IN ('sending', 'paused')", [$c['id']]);
                    Database::run("UPDATE campaign_recipients SET status = 'skipped', error = 'Campaign cancelled' WHERE campaign_id = ? AND status = 'queued'", [$c['id']]);
                    flash('success', 'Campaign cancelled. Nothing more will be sent.');
                    break;
                case 'send_batch':
                case 'send_now':
                    $report = Campaigns::run($action === 'send_now' ? 1 : 3, false, $action === 'send_now');
                    flash(preg_match('/^Sent [1-9]/', $report) ? 'success' : 'error', $report);
                    break;
                case 'delete':
                    if ($c['status'] === 'sending') {
                        flash('error', 'Pause or cancel the campaign before deleting it.');
                        break;
                    }
                    Database::run('DELETE FROM campaigns WHERE id = ?', [$c['id']]);
                    Auth::audit((int) $user['id'], 'campaign_delete', $c['name']);
                    flash('success', 'Campaign deleted.');
                    redirect(url('admin/campaigns'));
            }
            redirect($back);
        }
        if (!$editable) {
            flash('error', 'Pause the campaign before editing it.');
            redirect(url('admin/campaigns/' . $c['id']));
        }
        $data = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'subject' => trim((string) ($_POST['subject'] ?? '')),
            'body' => trim(str_replace("\r\n", "\n", (string) ($_POST['body'] ?? ''))),
            'layout' => ($_POST['layout'] ?? '') === 'branded' ? 'branded' : 'plain',
            'from_name' => mb_substr(trim((string) ($_POST['from_name'] ?? '')), 0, 80),
            'reply_to' => strtolower(trim((string) ($_POST['reply_to'] ?? ''))),
            'audience_tag' => Campaigns::cleanTags((string) ($_POST['audience_tag'] ?? '')),
        ];
        if ($data['name'] === '' || mb_strlen($data['name']) > 120) $errors['name'] = 'Give the campaign a name (120 characters at most).';
        if ($data['subject'] === '' || mb_strlen($data['subject']) > 200) $errors['subject'] = 'Write a subject line (200 characters at most).';
        if (mb_strlen($data['body']) < 20 || mb_strlen($data['body']) > 20000) $errors['body'] = 'Write the email (20 to 20,000 characters).';
        if ($data['reply_to'] !== '' && !filter_var($data['reply_to'], FILTER_VALIDATE_EMAIL)) $errors['reply_to'] = 'Enter a valid reply-to email or leave it empty.';
        if (str_contains($data['audience_tag'], ',')) $errors['audience_tag'] = 'Choose one tag.';
        $values = array_merge($values, $data);
        if (!$errors) {
            if ($c) {
                Database::update('campaigns', (int) $c['id'], $data);
                $cid = (int) $c['id'];
            } else {
                $cid = Database::insert('campaigns', $data + ['created_by' => $user['id']]);
            }
            Auth::audit((int) $user['id'], $c ? 'campaign_update' : 'campaign_create', $data['name']);
            flash('success', 'Campaign saved.');
            redirect(url('admin/campaigns/' . $cid));
        }
    }
    $preview = null;
    if ($c) {
        $sample = Database::one("SELECT * FROM contacts WHERE status = 'subscribed'" . ($c['audience_tag'] !== '' ? ' AND FIND_IN_SET(?, tags) > 0' : '') . ' ORDER BY id LIMIT 1', $c['audience_tag'] !== '' ? [$c['audience_tag']] : [])
            ?? ['first_name' => 'Alex', 'last_name' => '', 'company' => 'Example Ltd', 'website' => 'example.com', 'country' => '', 'token' => str_repeat('0', 32), 'email' => 'alex@example.com'];
        [$ps, $pt] = Campaigns::build($c, $sample);
        $preview = ['to' => $sample['email'], 'subject' => $ps, 'text' => $pt];
    }
    return admin_view('campaign-edit', [
        'c' => $c, 'values' => $values, 'errors' => $errors, 'templates' => $templates, 'editable' => $editable, 'preview' => $preview,
        'tags' => Campaigns::tags(), 'audience' => Campaigns::audience((string) $values['audience_tag']),
        'stats' => $c ? Campaigns::stats((int) $c['id']) : null,
        'recentTotal' => $c ? (int) Database::value("SELECT COUNT(*) FROM campaign_recipients WHERE campaign_id = ? AND status <> 'queued'", [$c['id']]) : 0,
        'rPage' => admin_page(20, 'rp')[0],
        'recent' => $c ? Database::all('SELECT r.status, r.error, r.sent_at, ct.email FROM campaign_recipients r JOIN contacts ct ON ct.id = r.contact_id WHERE r.campaign_id = ? AND r.status <> \'queued\' ORDER BY r.sent_at DESC, r.id DESC LIMIT 20 OFFSET ' . admin_page(20, 'rp')[2], [$c['id']]) : [],
        'pageTitle' => $c ? $c['name'] : 'New campaign',
    ]);
}

/** Called every minute by the open campaign page, so small batches go out even without a cron job. */
function admin_campaign_tick(): never
{
    $user = Auth::user();
    if (!$user || (int) $user['must_change_password'] === 1 || !Auth::can('campaigns', $user) || !Security::verifyCsrf($_POST['_csrf'] ?? null)) {
        json_response(['ok' => false], 403);
    }
    json_response(['ok' => true, 'report' => Campaigns::run(1, false)]);
}
