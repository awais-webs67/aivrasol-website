<?php
// Database updates for sites installed with an older version. Each one runs once,
// in order, and is written so running it again does no harm.
declare(strict_types=1);

$hasColumn = function (PDO $pdo, string $table, string $column): bool {
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
    $st->execute([$table, $column]);
    return (int) $st->fetchColumn() > 0;
};

return [
    '2026_10_16_plan_categories' => function (PDO $pdo) use ($hasColumn): void {
        if (!$hasColumn($pdo, 'plans', 'category')) {
            $pdo->exec("ALTER TABLE plans ADD COLUMN category ENUM('project','hire') NOT NULL DEFAULT 'project' AFTER id");
        }
        if (!$hasColumn($pdo, 'plans', 'show_on_home')) {
            $pdo->exec('ALTER TABLE plans ADD COLUMN show_on_home TINYINT(1) NOT NULL DEFAULT 0 AFTER is_highlighted');
        }
        $data = [];
        foreach (require __DIR__ . '/data/plans.php' as $plan) {
            $data[$plan['title']] = $plan;
        }
        // Bring the three original starter plans in line with the new versions.
        $pdo->exec("UPDATE plans SET show_on_home = 1 WHERE title = 'Product Development'");
        $pdo->exec("UPDATE plans SET category = 'hire', show_on_home = 1, is_highlighted = 0, sort_order = 6 WHERE title = 'Dedicated Team'");
        $st = $pdo->prepare("UPDATE plans SET category = 'hire', show_on_home = 1, is_highlighted = 1, sort_order = 4, subtitle = ?, features = ?, q1_label = ?, q1_options = ? WHERE title = 'Hire a Developer' AND q1_options NOT LIKE '%[hire-roles]%'");
        $h = $data['Hire a Developer'];
        $st->execute([$h['subtitle'], $h['features'], $h['q1_label'], $h['q1_options']]);
        // Add the new plans that do not exist yet.
        $exists = $pdo->prepare('SELECT COUNT(*) FROM plans WHERE title = ?');
        foreach ($data as $title => $plan) {
            $exists->execute([$title]);
            if ((int) $exists->fetchColumn() === 0) {
                insert_row($pdo, 'plans', $plan);
            }
        }
    },
    '2026_10_16_hire_icons' => function (PDO $pdo): void {
        $st = $pdo->prepare("UPDATE offerings SET icon = ? WHERE type = 'hire' AND slug = ? AND icon IN ('code', 'cloud', 'phone')");
        foreach (['devops-engineer' => 'infinity', 'python-developer' => 'terminal', 'reactjs-developer' => 'atom', 'nodejs-developer' => 'server'] as $slug => $icon) {
            $st->execute([$icon, $slug]);
        }
    },
    '2026_10_16_notify_list' => function (PDO $pdo): void {
        // The old single "notify_email" setting becomes the first address of the new list.
        $old = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'notify_email'")->fetchColumn();
        if (is_string($old) && $old !== '') {
            $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('notify_emails', ?)")->execute([$old]);
        }
    },
    '2026_10_17_offices' => function (PDO $pdo): void {
        // schema.sql has already created the offices table.
        if ((int) $pdo->query('SELECT COUNT(*) FROM offices')->fetchColumn() > 0) {
            return;
        }
        // Move anything typed in the old Settings "Offices" box ("City | address" per line).
        $old = (string) $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'offices'")->fetchColumn();
        $order = 0;
        foreach (preg_split('/\R/', $old) ?: [] as $line) {
            $parts = array_map('trim', explode('|', $line, 2));
            if ($parts[0] === '') {
                continue;
            }
            insert_row($pdo, 'offices', ['country' => $parts[0], 'address' => $parts[1] ?? '', 'sort_order' => ++$order]);
        }
        if ($order === 0) {
            foreach (require __DIR__ . '/data/offices.php' as $office) {
                insert_row($pdo, 'offices', $office);
            }
        }
    },
    '2026_10_18_user_roles' => function (PDO $pdo) use ($hasColumn): void {
        // More roles than admin and editor, and a flag that asks for a new password at first sign in.
        $pdo->exec("ALTER TABLE users MODIFY role VARCHAR(20) NOT NULL DEFAULT 'editor'");
        if (!$hasColumn($pdo, 'users', 'must_change_password')) {
            $pdo->exec('ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active');
        }
    },
    '2026_10_19_blog' => function (PDO $pdo): void {
        // schema.sql has already created the posts table. Add the starter articles once.
        if ((int) $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn() === 0) {
            foreach (require __DIR__ . '/data/posts.php' as $post) {
                insert_row($pdo, 'posts', $post);
            }
        }
    },
    '2026_10_20_employees' => function (PDO $pdo): void {
        // schema.sql has already created the employees and employee_documents tables.
    },
    '2026_10_21_client_portal' => function (PDO $pdo): void {
        // schema.sql has already created the client portal tables.
    },
    '2026_10_22_campaigns' => function (PDO $pdo): void {
        // schema.sql has already created the campaign tables. Add the starter templates once.
        if ((int) $pdo->query('SELECT COUNT(*) FROM email_templates')->fetchColumn() === 0) {
            foreach (require __DIR__ . '/data/email_templates.php' as $tpl) {
                insert_row($pdo, 'email_templates', $tpl);
            }
        }
    },
    '2026_10_23_suppressions' => function (PDO $pdo): void {
        // schema.sql has already created email_suppressions.
    },
    '2026_10_24_system_checks' => function (PDO $pdo): void {
        // schema.sql has already created system_checks.
    },
    '2026_10_25_project_frames' => function (PDO $pdo) use ($hasColumn): void {
        // Device frames for portfolio screenshots, and an optional phone screenshot.
        if (!$hasColumn($pdo, 'projects', 'image_mobile')) {
            $pdo->exec("ALTER TABLE projects ADD COLUMN image_mobile VARCHAR(255) NOT NULL DEFAULT '' AFTER image");
        }
        if (!$hasColumn($pdo, 'projects', 'frame')) {
            $pdo->exec("ALTER TABLE projects ADD COLUMN frame VARCHAR(10) NOT NULL DEFAULT 'auto' AFTER image_mobile");
        }
    },
    '2026_10_26_project_themes' => function (PDO $pdo) use ($hasColumn): void {
        if (!$hasColumn($pdo, 'projects', 'theme')) {
            $pdo->exec("ALTER TABLE projects ADD COLUMN theme VARCHAR(10) NOT NULL DEFAULT 'auto' AFTER frame");
        }
    },
    '2026_10_27_office_whatsapp' => function (PDO $pdo) use ($hasColumn): void {
        if (!$hasColumn($pdo, 'offices', 'whatsapp')) {
            $pdo->exec("ALTER TABLE offices ADD COLUMN whatsapp VARCHAR(40) NOT NULL DEFAULT '' AFTER email");
        }
    },
    '2026_10_28_team_pages' => function (PDO $pdo): void {
        // schema.sql has already created team_profiles (personal portfolio pages at /team/{slug}).
    },
    '2026_10_29_formal_autoreplies' => function (PDO $pdo): void {
        // Swap the old friendly auto-replies for the formal ones, but only where nobody has edited them.
        $next = "Here is what happens next:\n- One of our experts reads your request today.\n- We reply within one business day, usually much sooner.\n- If it helps, we set up a short call to understand your goals.";
        $old = [
            'contact' => ['We received your message, {name}', "Thank you for contacting {site_name}. We have received your message and our team is already looking at it.\n\n$next"],
            'consultation' => ['Your free consultation request, {name}', "Thank you for booking a free consultation with {site_name}.\n\n$next\n\nTo save time, feel free to reply to this email with any documents, links or ideas you would like us to see."],
            'quote' => ['Your free quote is on its way, {name}', "Thank you for asking {site_name} for a quote. We will study your needs and send you a clear, fixed quote with no hidden fees.\n\n$next"],
            'hire' => ['About hiring a developer, {name}', "Thank you for your interest in hiring a developer from {site_name}. We will shortlist suitable, pre-vetted developers for you to interview.\n\n$next"],
            'application' => ['We received your application, {name}', "Thank you for applying to {site_name}. Our HR team will review your CV carefully.\n\nIf your profile matches the role, we will contact you to arrange an interview. Because we receive many applications, we can only reply to shortlisted candidates.\n\nWe wish you the best of luck."],
        ];
        $new = require __DIR__ . '/data/autoreplies.php';
        $get = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
        $set = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        foreach ($old as $type => [$oldSubject, $oldBody]) {
            foreach (['subject' => $oldSubject, 'body' => $oldBody] as $part => $was) {
                $key = "autoreply_{$type}_{$part}";
                $get->execute([$key]);
                $now = $get->fetchColumn();
                if ($now === false || trim(str_replace("\r\n", "\n", (string) $now)) === '' || str_replace("\r\n", "\n", (string) $now) === $was) {
                    $set->execute([$key, $new[$type][$part]]);
                }
            }
        }
    },
    '2026_10_30_people' => function (PDO $pdo) use ($hasColumn): void {
        // Employees and team logins are one list now. HR additions wait for an admin's approval.
        if (!$hasColumn($pdo, 'employees', 'approval')) {
            $pdo->exec("ALTER TABLE employees ADD COLUMN approval VARCHAR(10) NOT NULL DEFAULT 'approved' AFTER status,
                ADD COLUMN created_by INT UNSIGNED NULL AFTER approval, ADD COLUMN approved_by INT UNSIGNED NULL AFTER created_by, ADD COLUMN approved_at DATETIME NULL AFTER approved_by");
        }
    },
    '2026_10_31_staff_messages' => function (PDO $pdo): void {
        // schema.sql has already created staff_messages (messages and notices to the team).
    },
    '2026_11_01_doc_templates' => function (PDO $pdo): void {
        // schema.sql has already created doc_templates; install.php adds the ready-made ones.
    },
];
