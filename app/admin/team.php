<?php
declare(strict_types=1);

/* ---------- Personal portfolio pages (/team/{slug}) ---------- */

/** The signed-in team member edits their own portfolio page. */
function admin_portfolio(): string
{
    $user = Auth::require('profile');
    $row = Team::forUser((int) $user['id']);
    $isAdmin = Auth::can('users', $user);
    $errors = [];
    $draft = Team::decode($row['draft']) ?: Team::starter($user);
    if (is_post() && (int) $row['allowed']) {
        admin_csrf();
        $action = (string) ($_POST['action'] ?? 'save');
        $before = team_photos($row);
        if ($action === 'offline' && $row['slug']) {
            Seo::ping([absolute_url('team/' . $row['slug']), absolute_url('team')]);
        }
        if ($action === 'offline') {
            Database::run('UPDATE team_profiles SET live = NULL, slug = NULL, pending = 0 WHERE id = ?', [$row['id']]);
            team_cleanup_photos((int) $row['id'], $before);
            Auth::audit((int) $user['id'], 'portfolio_offline');
            flash('success', 'Your page is offline. Your draft is kept, so you can publish it again at any time.');
            redirect(url('admin/portfolio'));
        }
        if ($action === 'remove_photo') {
            $draft['photo'] = '';
            Database::run('UPDATE team_profiles SET draft = ? WHERE id = ?', [json_encode($draft, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $row['id']]);
            team_cleanup_photos((int) $row['id'], $before);
            flash('success', 'Photo removed from your draft.');
            redirect(url('admin/portfolio'));
        }
        [$new, $errors] = Team::fromPost($_POST, $draft, (int) $row['id']);
        if (!$errors && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $new['photo'] = Upload::image($_FILES['photo'], 'team', 800);
            } catch (Throwable $e) {
                $errors[] = $e instanceof InvalidArgumentException ? $e->getMessage() : 'The photo could not be saved.';
            }
        }
        if ($errors && $new['photo'] !== ($draft['photo'] ?? '')) {
            $before[] = $new['photo'];
        }
        $draft = $new;
        if (!$errors) {
            $before[] = (string) ($draft['photo'] ?? '');
            Database::run('UPDATE team_profiles SET draft = ? WHERE id = ?', [json_encode($draft, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $row['id']]);
            if ($action === 'publish') {
                if (Team::approvalOn() && !$isAdmin) {
                    Database::run('UPDATE team_profiles SET pending = 1, submitted_at = NOW(), admin_note = "" WHERE id = ?', [$row['id']]);
                    team_notify_admins($user, $draft);
                    flash('success', 'Saved and sent for review. You will get an email when it is approved.' . ($row['live'] ? ' Until then, your current page stays as it is.' : ''));
                } else {
                    Team::publish((int) $row['id']);
                    flash('success', (int) $row['hidden'] ? 'Saved. Your page is hidden by an admin, so it is not public yet.' : 'Published. Your page is live.');
                }
                Auth::audit((int) $user['id'], 'portfolio_publish', $draft['slug']);
                team_cleanup_photos((int) $row['id'], $before);
            } else {
                team_cleanup_photos((int) $row['id'], $before);
                flash('success', 'Draft saved. Nothing changes on the website until you publish.');
            }
            redirect(url('admin/portfolio'));
        }
    }
    $row = Team::forUser((int) $user['id']);
    return admin_view('portfolio', [
        'row' => $row,
        'draft' => $draft,
        'errors' => $errors,
        'state' => Team::state($row),
        'needsReview' => Team::approvalOn() && !$isAdmin,
        'isAdmin' => $isAdmin,
        'pageTitle' => 'My portfolio page',
    ]);
}

/** Shows a draft exactly as the public page will look, to its owner or to an admin. */
function admin_portfolio_preview(?string $id = null): string
{
    $user = Auth::require('profile');
    if ($id !== null) {
        Auth::require('users');
        $row = ctype_digit($id) ? Database::one('SELECT * FROM team_profiles WHERE id = ?', [(int) $id]) : null;
    } else {
        $row = Team::forUser((int) $user['id']);
    }
    $m = $row ? Team::decode($row['draft']) : [];
    if (!$m) {
        abort(404);
    }
    admin_headers();
    return view('pages/member', [
        'm' => $m,
        'preview' => true,
        'pageTitle' => 'Preview: ' . $m['name'],
        'metaRobots' => 'noindex, nofollow',
        'crumbs' => [],
    ]);
}

/** Admins choose who may have a page, review drafts and can take any page offline. */
function admin_team_pages(): string
{
    $user = Auth::require('users');
    if (is_post()) {
        admin_csrf();
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'settings') {
            $stmt = Database::pdo()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
            $stmt->execute(['team_approval', empty($_POST['team_approval']) ? '0' : '1']);
            $stmt->execute(['team_list', empty($_POST['team_list']) ? '0' : '1']);
            Auth::audit((int) $user['id'], 'team_settings');
            flash('success', 'Settings saved.');
            redirect(url('admin/team-pages'));
        }
        $uid = ctype_digit((string) ($_POST['user_id'] ?? '')) ? (int) $_POST['user_id'] : 0;
        $member = $uid ? Database::one('SELECT id, name, email FROM users WHERE id = ?', [$uid]) : null;
        if (!$member) {
            flash('error', 'Team member not found.');
            redirect(url('admin/team-pages'));
        }
        $row = Team::forUser($uid);
        $name = $member['name'];
        switch ($action) {
            case 'allow':
                Database::run('UPDATE team_profiles SET allowed = 1 WHERE id = ?', [$row['id']]);
                team_mail($member, 'You can now create your portfolio page', "Hi {first},\n\nYou can now create your own portfolio page on the {site} website. Add your experience, skills and best work, then publish it.", 'Create my page', absolute_url('admin/portfolio'));
                flash('success', "$name can now create a portfolio page. They have been emailed.");
                break;
            case 'revoke':
                Database::run('UPDATE team_profiles SET allowed = 0, pending = 0 WHERE id = ?', [$row['id']]);
                flash('success', "Turned off for $name. Their page is offline. Their draft is kept in case you allow it again.");
                break;
            case 'approve':
                if (!$row['draft']) {
                    flash('error', 'There is nothing to approve yet.');
                    break;
                }
                Team::publish((int) $row['id']);
                team_cleanup_photos((int) $row['id'], team_photos($row));
                $slug = (string) Database::value('SELECT slug FROM team_profiles WHERE id = ?', [$row['id']]);
                team_mail($member, 'Your portfolio page is live', "Hi {first},\n\nGood news: your portfolio page has been approved and is now live on the {site} website. Share the link on LinkedIn, in your email signature and with clients.", 'View my page', absolute_url('team/' . $slug));
                flash('success', "Approved. $name's page is live" . ((int) $row['hidden'] ? ', but still hidden. Click Show to make it public.' : '.'));
                break;
            case 'sendback':
                $note = mb_substr(trim((string) ($_POST['note'] ?? '')), 0, 500);
                if ($note === '') {
                    flash('error', 'Write a short note so they know what to change.');
                    break;
                }
                Database::run('UPDATE team_profiles SET pending = 0, admin_note = ? WHERE id = ?', [$note, $row['id']]);
                team_mail($member, 'Changes needed on your portfolio page', "Hi {first},\n\nThanks for sending your portfolio page. Please make these changes and send it again:\n\n$note", 'Edit my page', absolute_url('admin/portfolio'));
                flash('success', "Sent back to $name with your note.");
                break;
            case 'hide':
            case 'show':
                Database::run('UPDATE team_profiles SET hidden = ? WHERE id = ?', [$action === 'hide' ? 1 : 0, $row['id']]);
                flash('success', $action === 'hide' ? "$name's page is hidden from the website." : "$name's page is visible again.");
                break;
            default:
                flash('error', 'Unknown action.');
        }
        Auth::audit((int) $user['id'], 'team_' . $action, $member['email']);
        if (in_array($action, ['hide', 'show', 'revoke', 'allow'], true) && $row['slug']) {
            Seo::ping([absolute_url('team/' . $row['slug']), absolute_url('team')]);
        }
        redirect(url('admin/team-pages'));
    }
    $rows = Database::all('SELECT u.id AS user_id, u.name, u.email, u.role, u.is_active, t.id, t.allowed, t.slug, t.draft, t.live, t.pending, t.hidden, t.admin_note, t.submitted_at, t.published_at
        FROM users u LEFT JOIN team_profiles t ON t.user_id = u.id ORDER BY t.pending DESC, u.is_active DESC, u.name');
    foreach ($rows as &$r) {
        $r['state'] = $r['id'] ? Team::state($r) : ['off', 'Not allowed'];
        $r['d'] = Team::decode($r['draft']);
    }
    unset($r);
    return admin_view('team-pages', ['rows' => $rows, 'pageTitle' => 'Team portfolio pages']);
}

/** Photos a profile row uses now (draft and live copy). */
function team_photos(?array $row): array
{
    return array_values(array_filter([Team::decode($row['draft'] ?? null)['photo'] ?? '', Team::decode($row['live'] ?? null)['photo'] ?? '']));
}

/** Deletes photos from $before that the profile no longer uses. */
function team_cleanup_photos(int $id, array $before): void
{
    $keep = team_photos(Database::one('SELECT draft, live FROM team_profiles WHERE id = ?', [$id]));
    foreach (array_unique($before) as $p) {
        if (!in_array($p, $keep, true) && preg_match('#^uploads/team/[a-f0-9]{24}\.webp$#', $p) && is_file(PUBLIC_PATH . '/' . $p)) {
            @unlink(PUBLIC_PATH . '/' . $p);
        }
    }
}

function team_notify_admins(array $user, array $draft): void
{
    $site = setting('site_name', 'AivraSol');
    $admins = Database::all("SELECT email FROM users WHERE role = 'admin' AND is_active = 1 AND id <> ?", [$user['id']]);
    if (!$admins) {
        return;
    }
    [$text, $html] = Emails::notice('Portfolio page to review', Emails::safeName($user['name']) . ' sent their portfolio page (' . ($draft['headline'] ?? '') . ") for review.\n\nOpen it, check the text and links, then approve it or send it back with a note.", 'Review the page', absolute_url('admin/team-pages'));
    Mailer::queue(array_column($admins, 'email'), "Review needed: {$user['name']}'s portfolio page on $site", $text, $html, null, 'team_review');
}

function team_mail(array $member, string $title, string $body, string $button, string $link): void
{
    $site = setting('site_name', 'AivraSol');
    $first = strtok(Emails::safeName($member['name']), ' ') ?: 'there';
    $body = str_replace(['{first}', '{site}'], [$first, $site], $body);
    [$text, $html] = Emails::notice($title, $body, $button, $link);
    Mailer::queue($member['email'], "$title ($site)", $text, $html, null, 'team_page');
}
