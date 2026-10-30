<?php
class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;

        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        // Cierre por inactividad
        $now = time();
        if (isset($_SESSION['user'], $_SESSION['_last']) && ($now - $_SESSION['_last']) > SESSION_IDLE_MIN * 60) {
            unset($_SESSION['user'], $_SESSION['pass_default']);
            flash('warning', 'Tu sesión se cerró por inactividad. Ingresa de nuevo.');
        }
        $_SESSION['_last'] = $now;
    }
}
