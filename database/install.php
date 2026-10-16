<?php
/**
 * Creates the tables and loads the starter content using the database details in app/config.php.
 * Command line only:  php database/install.php
 * Start again from empty (deletes all data!):  php database/install.php --fresh
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
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

    if ((int) $pdo->query('SELECT COUNT(*) FROM offerings')->fetchColumn() > 0) {
        echo "Starter content already loaded, skipped. Use --fresh to start again.\n";
    } else {
        $pdo->exec((string) file_get_contents(__DIR__ . '/seed.sql'));
        echo "Starter content loaded.\n";
    }
    echo "Done. Next: open /admin/setup in your browser to create your admin account.\n";
} catch (PDOException $e) {
    fwrite(STDERR, 'Database error: ' . $e->getMessage() . "\nCheck the db section in app/config.php and that MySQL is running.\n");
    exit(1);
}
