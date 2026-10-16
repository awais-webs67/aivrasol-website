<?php
declare(strict_types=1);

require_once APP_PATH . '/lib/Backup.php';

function admin_backups(): string
{
    $user = Auth::require('settings');
    if (is_post()) {
        admin_csrf();
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'database' || $action === 'full') {
                if (!Security::rateLimit('backup_make', 6, 3600, (string) $user['id'])) {
                    throw new RuntimeException('Too many backups in an hour. Please wait.');
                }
                $name = $action === 'full' ? Backup::full() : Backup::database();
                Backup::prune(max(1, (int) setting('backup_keep', '7')));
                Auth::audit((int) $user['id'], 'backup_' . $action, $name);
                flash('success', 'Backup ready: ' . $name . '. Download it and keep a copy somewhere safe.');
            } elseif ($action === 'delete' && ($p = Backup::path((string) ($_POST['name'] ?? '')))) {
                unlink($p);
                Auth::audit((int) $user['id'], 'backup_delete', basename($p));
                flash('success', 'Backup deleted.');
            } elseif ($action === 'settings') {
                $save = Database::pdo()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
                $save->execute(['backup_auto', isset($_POST['backup_auto']) ? '1' : '0']);
                $save->execute(['backup_keep', (string) max(2, min(60, (int) ($_POST['backup_keep'] ?? 7)))]);
                flash('success', 'Backup settings saved.');
            }
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
        }
        redirect(url('admin/backups'));
    }
    return admin_view('backups', ['backups' => Backup::list(), 'pageTitle' => 'Backups']);
}

function admin_backup_download(string $name): never
{
    $user = Auth::require('settings');
    $path = Backup::path($name) ?? abort(404);
    Auth::audit((int) $user['id'], 'backup_download', $name);
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    readfile($path);
    exit;
}
