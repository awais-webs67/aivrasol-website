<?php
declare(strict_types=1);

/**
 * Backups for safe hosting moves and disaster recovery.
 * "Database" backups are a gzipped SQL file. "Full" backups are one ZIP with the database and every uploaded file
 * (website images, staff photos, signatures, CVs and portal files). They are stored in storage/backups, which is never public.
 */
final class Backup
{
    public const SEP = "/*--avs--*/\n";
    private const SKIP_DATA = ['rate_limits'];

    public static function dir(): string
    {
        $dir = STORAGE_PATH . '/backups';
        if (!is_dir($dir) && !mkdir($dir, 0750, true)) {
            throw new RuntimeException('storage/backups is not writable.');
        }
        return $dir;
    }

    private static function name(string $kind, string $ext): string
    {
        return 'aivrasol-' . $kind . '-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
    }

    /** Writes the whole database as SQL into a stream (works without mysqldump, which many hosts do not allow). */
    private static function dumpTo(callable $write): void
    {
        $pdo = Database::pdo();
        $write("-- AivraSol database backup, " . gmdate('Y-m-d H:i:s') . " UTC\nSET NAMES utf8mb4;\n" . self::SEP . "SET FOREIGN_KEY_CHECKS = 0;\n" . self::SEP);
        foreach ($pdo->query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'')->fetchAll(PDO::FETCH_NUM) as [$table]) {
            $t = str_replace('`', '``', $table);
            $create = $pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM)[1];
            $write("DROP TABLE IF EXISTS `$t`;\n" . self::SEP . $create . ";\n" . self::SEP);
            if (in_array($table, self::SKIP_DATA, true)) {
                continue;
            }
            // Unbuffered, so big tables stream row by row instead of filling memory.
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            $stmt = $pdo->query("SELECT * FROM `$t`");
            $batch = [];
            $cols = null;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $cols ??= '`' . implode('`, `', array_map(fn ($c) => str_replace('`', '``', $c), array_keys($row))) . '`';
                // MySQL joins adjacent string literals, so splitting the separator keeps any text intact on restore.
                $batch[] = '(' . implode(', ', array_map(fn ($v) => $v === null ? 'NULL' : str_replace('/*--avs--*/', "/*--avs-' '-*/", $pdo->quote((string) $v)), $row)) . ')';
                if (count($batch) >= 200) {
                    $write("INSERT INTO `$t` ($cols) VALUES\n" . implode(",\n", $batch) . ";\n" . self::SEP);
                    $batch = [];
                }
            }
            $stmt->closeCursor();
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            if ($batch) {
                $write("INSERT INTO `$t` ($cols) VALUES\n" . implode(",\n", $batch) . ";\n" . self::SEP);
            }
        }
        $write("SET FOREIGN_KEY_CHECKS = 1;\n" . self::SEP);
    }

    public static function database(): string
    {
        @set_time_limit(300);
        $name = self::name('database', 'sql.gz');
        $path = self::dir() . '/' . $name;
        $gz = gzopen($path, 'wb6');
        if (!$gz) {
            throw new RuntimeException('Could not create the backup file.');
        }
        self::dumpTo(fn (string $s) => gzwrite($gz, $s));
        gzclose($gz);
        @chmod($path, 0640);
        return $name;
    }

    /** Folders included in a full backup, as [path on disk => path inside the ZIP]. */
    public static function fileFolders(): array
    {
        return [
            PUBLIC_PATH . '/uploads' => 'files/public/uploads',
            STORAGE_PATH . '/private' => 'files/storage/private',
            STORAGE_PATH . '/cv' => 'files/storage/cv',
        ];
    }

    public static function full(): string
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Your host has no PHP zip extension. Use a database backup and copy the folders by FTP.');
        }
        @set_time_limit(600);
        $name = self::name('full', 'zip');
        $path = self::dir() . '/' . $name;
        $tmp = self::dir() . '/.dump-' . bin2hex(random_bytes(6)) . '.sql';
        $fh = fopen($tmp, 'wb');
        self::dumpTo(fn (string $s) => fwrite($fh, $s));
        fclose($fh);
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            throw new RuntimeException('Could not create the ZIP file.');
        }
        $zip->addFile($tmp, 'database.sql');
        $zip->addFromString('README.txt', "AivraSol full backup made " . gmdate('Y-m-d H:i') . " UTC.\nRestore on any host with: php database/restore.php storage/backups/$name\n");
        foreach (self::fileFolders() as $src => $inZip) {
            if (!is_dir($src)) {
                continue;
            }
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if ($file->isFile() && $file->getFilename() !== '.gitkeep') {
                    $zip->addFile($file->getPathname(), $inZip . '/' . str_replace('\\', '/', substr($file->getPathname(), strlen($src) + 1)));
                }
            }
        }
        $zip->close();
        @unlink($tmp);
        @chmod($path, 0640);
        return $name;
    }

    public static function list(): array
    {
        $out = [];
        foreach (glob(self::dir() . '/aivrasol-*') ?: [] as $f) {
            $out[] = ['name' => basename($f), 'size' => filesize($f), 'time' => filemtime($f), 'kind' => str_contains(basename($f), '-full-') ? 'Full' : 'Database'];
        }
        usort($out, fn ($a, $b) => $b['time'] <=> $a['time']);
        return $out;
    }

    public static function path(string $name): ?string
    {
        if (!preg_match('/^aivrasol-(database|full)-\d{8}-\d{6}-[a-f0-9]{6}\.(sql\.gz|zip)$/', $name)) {
            return null;
        }
        $p = self::dir() . '/' . $name;
        return is_file($p) ? $p : null;
    }

    /** Keeps the newest $keep backups of each kind. */
    public static function prune(int $keep): void
    {
        foreach (['Database', 'Full'] as $kind) {
            $files = array_values(array_filter(self::list(), fn ($b) => $b['kind'] === $kind));
            foreach (array_slice($files, max(1, $keep)) as $old) {
                @unlink(self::dir() . '/' . $old['name']);
            }
        }
    }
}
