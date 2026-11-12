<?php
declare(strict_types=1);

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    exit('Este sistema necesita PHP 8.1 o superior. Tu versión es ' . PHP_VERSION . '.');
}
foreach (['pdo_mysql', 'mbstring'] as $ext) {
    if (!extension_loaded($ext)) {
        http_response_code(500);
        exit("Falta la extensión de PHP \"$ext\". Actívala en php.ini y reinicia Apache.");
    }
}

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require APP_PATH . '/config/config.php';

// BASE_URL: ruta donde vive la app (ej. /karaoke_bar), sin /public
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


