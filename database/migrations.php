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
];
