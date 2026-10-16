<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('VIEW_PATH', APP_PATH . '/views');

$configFile = APP_PATH . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Configuration missing. Copy app/config.sample.php to app/config.php.');
}
$GLOBALS['config'] = require $configFile;

require APP_PATH . '/lib/helpers.php';
require APP_PATH . '/lib/Database.php';
require APP_PATH . '/lib/Security.php';
require APP_PATH . '/lib/Validator.php';
require APP_PATH . '/lib/Mailer.php';
require APP_PATH . '/lib/Auth.php';
require APP_PATH . '/lib/Router.php';
require APP_PATH . '/lib/Upload.php';
require APP_PATH . '/lib/Content.php';

$isDev = config('env') === 'development';
error_reporting(E_ALL);
ini_set('display_errors', $isDev ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/php-error.log');
ini_set('expose_php', '0');
date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

set_exception_handler(function (Throwable $e) use ($isDev) {
    error_log('[' . date('c') . '] ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    if ($isDev) {
        echo '<pre>' . e((string) $e) . '</pre>';
        return;
    }
    try {
        echo view('pages/error', ['code' => 500, 'pageTitle' => 'Something went wrong']);
    } catch (Throwable $inner) {
        echo 'Something went wrong. Please try again later.';
    }
});

Security::startSession();
Security::sendHeaders();
