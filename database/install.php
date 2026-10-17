<?php
/**
 * Installs or updates the database using the details in app/config.php.
 * Command line only.
 *   php database/install.php           first install, or safe update of an existing site
 *   php database/install.php --fresh   delete everything and start again (all data is lost!)
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

function insert_row(PDO $pdo, string $table, array $row): void
{
    $cols = array_keys($row);
    $sql = sprintf('INSERT INTO `%s` (`%s`) VALUES (%s)', $table, implode('`, `', $cols), implode(', ', array_fill(0, count($cols), '?')));
    $pdo->prepare($sql)->execute(array_values($row));
}

$root = dirname(__DIR__);
$configFile = $root . '/app/config.php';
if (!is_file($configFile)) {
    fwrite(STDERR, "app/config.php not found. Copy app/config.sample.php to app/config.php and fill in the database details first.\n");
    exit(1);
}
$db = (require $configFile)['db'];

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $db['host'], (int) $db['port']),
        $db['user'],
        $db['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $name = str_replace('`', '', $db['name']);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$name`");

    if (in_array('--fresh', $argv, true)) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
            $pdo->exec('DROP TABLE `' . str_replace('`', '', $table) . '`');
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        echo "Old tables removed.\n";
    }

    $pdo->exec((string) file_get_contents(__DIR__ . '/schema.sql'));
    echo "Tables ready.\n";

    $migrations = require __DIR__ . '/migrations.php';
    $done = $pdo->query('SELECT name FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    $mark = $pdo->prepare('INSERT IGNORE INTO schema_migrations (name) VALUES (?)');

    if ((int) $pdo->query('SELECT COUNT(*) FROM offerings')->fetchColumn() === 0) {
        $pdo->exec((string) file_get_contents(__DIR__ . '/seed.sql'));
        foreach (require __DIR__ . '/data/plans.php' as $plan) {
            insert_row($pdo, 'plans', $plan);
        }
        foreach (require __DIR__ . '/data/offices.php' as $office) {
            insert_row($pdo, 'offices', $office);
        }
        foreach (require __DIR__ . '/data/posts.php' as $post) {
            insert_row($pdo, 'posts', $post);
        }
        foreach (require __DIR__ . '/data/email_templates.php' as $tpl) {
            insert_row($pdo, 'email_templates', $tpl);
        }
        foreach (array_keys($migrations) as $m) {
            $mark->execute([$m]);
        }
        echo "Starter content loaded.\n";
    } else {
        $ran = 0;
        foreach ($migrations as $m => $fn) {
            if (!in_array($m, $done, true)) {
                $fn($pdo);
                $mark->execute([$m]);
                echo "Update applied: $m\n";
                $ran++;
            }
        }
        echo $ran ? "Your existing content was kept.\n" : "Database is already up to date.\n";
    }

    // Ready-made certificate and letter templates, added once. An edited experience letter text is kept.
    if ((int) $pdo->query('SELECT COUNT(*) FROM doc_templates')->fetchColumn() === 0) {
        $custom = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'doc_letter_template'")->fetchColumn();
        foreach (require __DIR__ . '/data/doc_templates.php' as $tpl) {
            if ($tpl['code'] === 'EXP' && is_string($custom) && trim($custom) !== '') {
                $tpl['body'] = $custom;
            }
            insert_row($pdo, 'doc_templates', $tpl + ['is_builtin' => 1]);
        }
        echo "Certificate templates added.\n";
    }

    $add = $pdo->prepare('INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)');
    $before = (int) $pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn();
    foreach (require __DIR__ . '/data/settings.php' as $k => $v) {
        $add->execute([$k, $v]);
    }
    $added = (int) $pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn() - $before;
    if ($added > 0) {
        echo "New settings added: $added\n";
    }

    echo "Done. If you have no admin account yet, open /admin/setup in your browser.\n";
} catch (PDOException $e) {
    fwrite(STDERR, 'Database error: ' . $e->getMessage() . "\nCheck the db section in app/config.php and that MySQL is running.\n");
    exit(1);
}
