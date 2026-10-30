<?php
// Diagnóstico de instalación. Abre: http://localhost/karaoke_bar/diagnostico.php
// Compatible con PHP antiguo a propósito, para poder avisar si tu PHP es muy viejo.
// BORRA este archivo cuando el sistema funcione bien.
define('ROOT_PATH', dirname(__DIR__));
require ROOT_PATH . '/app/config/config.php';

if (APP_ENV !== 'development') { http_response_code(404); exit; }

$items = array();
function chk(&$items, $ok, $titulo, $detalle, $ayuda = '') {
    $items[] = array('ok' => $ok, 'titulo' => $titulo, 'detalle' => $detalle, 'ayuda' => $ayuda);
}
function h($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

// 1. Versión de PHP
chk($items, version_compare(PHP_VERSION, '8.1.0', '>='), 'Versión de PHP (se necesita 8.1 o superior)', 'Tienes PHP ' . PHP_VERSION,
    'Instala una versión reciente de XAMPP (apachefriends.org) con PHP 8.1 o más.');

// 2. Extensiones
foreach (array('pdo_mysql', 'mbstring', 'json', 'session') as $ext) {
    chk($items, extension_loaded($ext), "Extensión $ext", extension_loaded($ext) ? 'Activa' : 'No está activa',
        "En XAMPP: Apache > Config > PHP (php.ini), quita el ; delante de extension=$ext y reinicia Apache.");
}

// 3. mod_rewrite
if (function_exists('apache_get_modules')) {
    $rw = in_array('mod_rewrite', apache_get_modules(), true);
    chk($items, $rw, 'Apache mod_rewrite', $rw ? 'Activo' : 'No está activo',
        'En httpd.conf (XAMPP > Apache > Config) quita el # de LoadModule rewrite_module y reinicia Apache.');
}

// 4. Carpeta de sesiones
$sp = session_save_path() !== '' ? session_save_path() : sys_get_temp_dir();
chk($items, is_dir($sp) && is_writable($sp), 'Carpeta de sesiones escribible', 'Ruta: ' . $sp,
    'Sin esto no se puede iniciar sesión. Revisa los permisos de esa carpeta.');

// 5. Base de datos
$pdo = null;
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
    chk($items, true, 'Conexión a MySQL', 'Conectado a la base "' . DB_NAME . '" con el usuario "' . DB_USER . '"');
} catch (Exception $e) {
    chk($items, false, 'Conexión a MySQL', $e->getMessage(),
        'Revisa que MySQL esté encendido en XAMPP, que hayas importado karaoke_bar.sql y que DB_USER / DB_PASS en app/config/config.php sean correctos.');
}

if ($pdo) {
    $esperadas = array('roles','usuarios','categorias','productos','movimientos_stock','combos','combo_productos','mesas',
        'sesiones_mesa','reservas','metodos_pago','turnos_caja','ventas','detalle_venta','cola_canciones','bitacora',
        'configuracion','v_stock_bajo','v_cola','v_resumen_turno');
    $existen = array();
    foreach ($pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM) as $r) { $existen[] = $r[0]; }
    $faltan = array_diff($esperadas, $existen);
    chk($items, !$faltan, 'Tablas y vistas de la base de datos',
        $faltan ? 'Faltan: ' . implode(', ', $faltan) : 'Están las ' . count($esperadas) . ' tablas y vistas',
        'La importación quedó incompleta. En phpMyAdmin borra la base karaoke_bar y vuelve a importar karaoke_bar.sql completo (mira si phpMyAdmin mostró algún error rojo).');

    if (in_array('usuarios', $existen, true) && in_array('roles', $existen, true)) {
        $n = (int) $pdo->query("SELECT COUNT(*) FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE u.usuario = 'admin' AND u.estado = 1")->fetchColumn();
        chk($items, $n === 1, 'Usuario admin activo', $n === 1 ? 'Existe' : 'No se encontró',
            'Importa de nuevo karaoke_bar.sql: el usuario admin se crea al final del archivo.');
    }
}
$todoBien = true; foreach ($items as $i) { if (!$i['ok']) { $todoBien = false; } }
?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Diagnóstico</title>
<style>
body{font-family:system-ui,Segoe UI,sans-serif;background:#22101C;color:#F4EADF;max-width:760px;margin:2rem auto;padding:0 1rem;line-height:1.5}
h1{font-weight:400;font-size:1.8rem} li{list-style:none;margin:0 0 .9rem;padding:.8rem 1rem;border-radius:8px;background:#2D1626;border-left:4px solid #7FB89A}
li.mal{border-left-color:#E5604D} b{display:block} small{color:#B9A3AE;display:block} .ayuda{color:#E3C07F;margin-top:.3rem}
ul{padding:0} .res{padding:1rem;border-radius:8px;margin:1rem 0;background:#3A1E31}
</style></head><body>
<h1>Diagnóstico de instalación</h1>
<div class="res"><?= $todoBien ? 'Todo está en orden. Si aun así ves un error, copia el mensaje que aparece en pantalla y envíalo.' : 'Hay cosas por corregir. Revisa las marcadas en rojo.' ?></div>
<ul>
<?php foreach ($items as $i): ?>
  <li class="<?= $i['ok'] ? 'bien' : 'mal' ?>">
    <b><?= $i['ok'] ? '✔' : '✘' ?> <?= h($i['titulo']) ?></b>
    <small><?= h($i['detalle']) ?></small>
    <?php if (!$i['ok'] && $i['ayuda']): ?><div class="ayuda"><?= h($i['ayuda']) ?></div><?php endif; ?>
  </li>
<?php endforeach; ?>
</ul>
<p><small style="color:#B9A3AE">Cuando todo funcione, borra el archivo public/diagnostico.php.</small></p>
</body></html>