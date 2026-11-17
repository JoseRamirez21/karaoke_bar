<?php
declare(strict_types=1);

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    exit('Este sistema necesita PHP 8.1 o superior. Tu versión es ' . PHP_VERSION . '.');
}


define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

$settingsFile = is_file(APP_PATH . '/config/config.php') ? 'config.php' : 'config1.php';
$settings = require APP_PATH . '/config/' . $settingsFile;

define('DB_HOST', $settings['DB_HOST']);
define('DB_NAME', $settings['DB_NAME']);
define('DB_USER', $settings['DB_USER']);
define('DB_PASS', $settings['DB_PASS']);
define('APP_ENV', $settings['APP_ENV']);

const SESSION_NAME = 'KARAOKE_SESSION';
const SESSION_IDLE_MIN = 240;
const DIA_INICIO_HORA = 6;

date_default_timezone_set('America/Lima');


$script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
define('BASE_URL', rtrim((string) preg_replace('#/public$#', '', $script), '/'));

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}

spl_autoload_register(function (string $class): void {
    foreach (['core', 'controllers', 'models'] as $dir) {
        $file = APP_PATH . "/$dir/$class.php";
        if (is_file($file)) { require $file; return; }
    }
});

require APP_PATH . '/core/helpers.php';

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

Session::start();
(new Router())->dispatch();


