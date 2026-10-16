<?php
declare(strict_types=1);

require_once APP_PATH . '/lib/Campaigns.php';

/**
 * Background work: campaign sending and housekeeping.
 * Run by bin/cron.php every 5 minutes (best), or by the secret /cron/... address from an outside cron service.
 */
function run_scheduled_tasks(bool $cli): string
{
    $save = Database::pdo()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $save->execute(['cron_last_seen', gmdate('Y-m-d H:i:s')]);
    $out = [];

    // Spread the hourly limit across 12 runs an hour, with natural gaps between emails.
    $l = Campaigns::limits();
    $max = $cli ? max(1, (int) ceil($l['per_hour'] / 12)) : 2;
    $out[] = 'Campaigns: ' . Campaigns::run($max, $cli);

    // Housekeeping, at most once an hour.
    $last = setting('task_cleanup_at');
    if ($last === '' || strtotime($last . ' UTC') < time() - 3600) {
        Database::run('DELETE FROM rate_limits WHERE created_at < UTC_TIMESTAMP() - INTERVAL 2 DAY');
        Database::run('DELETE FROM password_resets WHERE expires_at < UTC_TIMESTAMP() - INTERVAL 1 DAY OR used_at IS NOT NULL');
        Database::run('DELETE FROM email_log WHERE created_at < UTC_TIMESTAMP() - INTERVAL 180 DAY');
        Database::run('DELETE FROM audit_log WHERE created_at < UTC_TIMESTAMP() - INTERVAL 365 DAY');
        $save->execute(['task_cleanup_at', gmdate('Y-m-d H:i:s')]);
        $out[] = 'Cleanup done.';
    }
    // Daily database backup (from the command line only, where there is no time limit).
    if ($cli && setting('backup_auto', '1') === '1') {
        $lastBackup = setting('task_backup_at');
        if ($lastBackup === '' || strtotime($lastBackup . ' UTC') < time() - 23 * 3600) {
            require_once APP_PATH . '/lib/Backup.php';
            try {
                $out[] = 'Backup: ' . Backup::database();
                Backup::prune(max(2, (int) setting('backup_keep', '7')));
            } catch (Throwable $e) {
                $out[] = 'Backup failed: ' . $e->getMessage();
                error_log('Backup failed: ' . $e->getMessage());
            }
            $save->execute(['task_backup_at', gmdate('Y-m-d H:i:s')]);
        }
    }
    return implode("\n", $out) . "\n";
}
