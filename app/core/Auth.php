<?php
class Auth
{
    // Hash de relleno para que el tiempo de respuesta sea igual si el usuario no existe.
    private const DUMMY_HASH = '$2y$10$ifKon//QTDqwCflK6iYABeuEsp3voiCuuq1BXX3TJSY6c2u1W11PW';
    private const PASSWORD_INICIAL = 'Admin123*';

    public static function check(): bool { return isset($_SESSION['user']); }
    public static function user(): ?array { return $_SESSION['user'] ?? null; }
    public static function id(): int { return (int) ($_SESSION['user']['id'] ?? 0); }
    public static function role(): string { return (string) ($_SESSION['user']['rol'] ?? ''); }
    public static function is(string ...$roles): bool { return in_array(self::role(), $roles, true); }

    /** @return array{0:bool,1:string} */
    public static function attempt(string $usuario, string $password): array
    {
        if (self::bloqueado()) {
            return [false, 'Demasiados intentos fallidos. Espera ' . BLOQUEO_MINUTOS . ' minutos e inténtalo de nuevo.'];
        }

        $u  = Usuario::buscarPorUsuario($usuario);
        $ok = password_verify($password, $u['password'] ?? self::DUMMY_HASH) && $u !== null && (int) $u['estado'] === 1;

        if (!$ok) {
            Bitacora::log('login_fallido', 'usuarios', null, 'Usuario: ' . $usuario);
            return [false, 'Usuario o contraseña incorrectos.'];
        }

        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int) $u['id'], 'nombre' => $u['nombre'], 'usuario' => $u['usuario'], 'rol' => $u['rol']];
        $_SESSION['pass_default'] = ($password === self::PASSWORD_INICIAL);

        if (password_needs_rehash($u['password'], PASSWORD_DEFAULT)) {
            Usuario::actualizarPassword((int) $u['id'], password_hash($password, PASSWORD_DEFAULT));
        }
        Usuario::registrarLogin((int) $u['id']);
        Bitacora::log('login', 'usuarios', (int) $u['id'], null, (int) $u['id']);
        return [true, ''];
    }

    public static function logout(): void
    {
        if (self::check()) Bitacora::log('logout', 'usuarios', self::id());
        unset($_SESSION['user'], $_SESSION['pass_default']);
        session_regenerate_id(true);
    }

    /** Revalida en cada petición: si desactivan al trabajador, se le cierra la sesión. */
    public static function refresh(): void
    {
        if (!self::check()) return;
        $u = Usuario::buscarPorId(self::id());
        if ($u === null || (int) $u['estado'] !== 1) {
            unset($_SESSION['user'], $_SESSION['pass_default']);
            flash('error', 'Tu cuenta está desactivada. Habla con el administrador.');
            return;
        }
        $_SESSION['user']['rol']    = $u['rol'];
        $_SESSION['user']['nombre'] = $u['nombre'];
    }

    private static function bloqueado(): bool
    {
        $n = (int) Database::value(
            "SELECT COUNT(*) FROM bitacora WHERE accion = 'login_fallido' AND ip = ? AND created_at > (NOW() - INTERVAL " . (int) BLOQUEO_MINUTOS . ' MINUTE)',
            [$_SERVER['REMOTE_ADDR'] ?? '']
        );
        return $n >= MAX_INTENTOS;
    }
}
