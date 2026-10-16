<?php
/**
 * Restores a backup made in Admin, Backups. Command line only. Use it to move to a new host or recover from a problem.
 *   php database/restore.php storage/backups/aivrasol-full-....zip        database and all uploaded files
 *   php database/restore.php storage/backups/aivrasol-database-....sql.gz database only
 * It replaces the current database tables with the ones in the backup. Run "php database/install.php" afterwards
 * to apply any updates that are newer than the backup.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$root = dirname(__DIR__);
$file = $argv[1] ?? '';
if ($file === '' || !is_file($file)) {
    fwrite(STDERR, "Usage: php database/restore.php path/to/backup.zip (or .sql.gz)\n");
    exit(1);
}
if (!in_array('--yes', $argv, true)) {
    echo "This replaces the database" . (str_ends_with($file, '.zip') ? ' and uploaded files' : '') . " with the backup. Type YES to continue: ";
    if (trim((string) fgets(STDIN)) !== 'YES') {
        echo "Cancelled.\n";
        exit(1);
    }
}
$db = (require $root . '/app/config.php')['db'];
$pdo = new PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $db['host'], (int) $db['port']), $db['user'], $db['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$name = str_replace('`', '', $db['name']);
$pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$name`");

$sep = "/*--avs--*/\n";
$runSql = function ($stream) use ($pdo, $sep): int {
    $buf = '';
    $count = 0;
    while (!feof($stream)) {
        $buf .= (string) fread($stream, 1 << 20);
        while (($pos = strpos($buf, $sep)) !== false) {
            $stmt = trim(substr($buf, 0, $pos));
            $buf = substr($buf, $pos + strlen($sep));
            $stmt = trim(preg_replace('/^--[^\n]*\n/m', '', $stmt) ?? '');
            if ($stmt !== '') {
                $pdo->exec($stmt);
                $count++;
            }
        }
    }
    if (trim(preg_replace('/^--[^\n]*\n?/m', '', $buf) ?? '') !== '') {
        throw new RuntimeException('This file was not made by the AivraSol backup tool. Import it with phpMyAdmin instead.');
    }
    return $count;
};

try {
    if (str_ends_with($file, '.zip')) {
        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            throw new RuntimeException('Could not open the ZIP file.');
        }
        $stream = $zip->getStream('database.sql');
        if (!$stream) {
            throw new RuntimeException('database.sql is missing from the backup.');
        }
        echo 'Database: ' . $runSql($stream) . " statements run.\n";
        fclose($stream);
        $targets = ['files/public/uploads/' => $root . '/public/uploads/', 'files/storage/private/' => $root . '/storage/private/', 'files/storage/cv/' => $root . '/storage/cv/'];
        $restored = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = (string) $zip->getNameIndex($i);
            foreach ($targets as $prefix => $dest) {
                if (!str_starts_with($entry, $prefix) || str_ends_with($entry, '/')) {
                    continue;
                }
                $rel = substr($entry, strlen($prefix));
                // Never write outside the target folder, and never restore anything that could run as code.
                if ($rel === '' || str_contains($rel, '..') || str_starts_with($rel, '/') || preg_match('/\.(php\d?|phtml|phar|htaccess|cgi|pl|py|sh)$/i', $rel)) {
                    continue;
                }
                $path = $dest . $rel;
                if (!is_dir(dirname($path))) {
                    mkdir(dirname($path), 0755, true);
                }
                $in = $zip->getStream($entry);
                $out = fopen($path, 'wb');
                stream_copy_to_stream($in, $out);
                fclose($in);
                fclose($out);
                $restored++;
            }
        }
        $zip->close();
        echo "Files: $restored restored.\n";
    } else {
        $stream = str_ends_with($file, '.gz') ? gzopen($file, 'rb') : fopen($file, 'rb');
        echo 'Database: ' . $runSql($stream) . " statements run.\n";
        fclose($stream);
    }
    echo "Done. Now run: php database/install.php\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Restore failed: ' . $e->getMessage() . "\n");
    exit(1);
}
