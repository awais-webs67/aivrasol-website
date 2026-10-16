<?php
/**
 * Background tasks: sends campaign emails within your limits and tidies old records.
 * Add this cron job in cPanel (Cron Jobs, every 5 minutes):
 *   php /home/YOUR_CPANEL_USER/public_html/bin/cron.php >/dev/null 2>&1
 * (use the real path to this file; on some hosts the PHP path is /usr/local/bin/php)
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';
require APP_PATH . '/tasks.php';
echo run_scheduled_tasks(true);
