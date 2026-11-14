<?php
class Auth
{
    
    private const DUMMY_HASH = '$2y$10$ifKon//QTDqwCflK6iYABeuEsp3voiCuuq1BXX3TJSY6c2u1W11PW';
    private const PASSWORD_INICIAL = 'Admin123*';

    public static function check(): bool { return isset($_SESSION['user']); }
    public static function user(): ?array { return $_SESSION['user'] ?? null; }
    public static function id(): int { return (int) ($_SESSION['user']['id'] ?? 0); }
    public static function role(): string { return (string) ($_SESSION['user']['rol'] ?? ''); }
    public static function is(string ...$roles): bool { return in_array(self::role(), $roles, true); }

    
    public static function attempt(string $usuario, string $password): array
    {

        $u  = Usuario::buscarPorUsuario($usuario);
        $ok = password_verify($password, $u['password'] ?? self::DUMMY_HASH) && $u !== null && (int) $u['estado'] === 1;

        if (!$ok) {
            return [false, 'Usuario o contraseña incorrectos.'];
        }

        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int) $u['id'], 'nombre' => $u['nombre'], 'usuario' => $u['usuario'], 'rol' => $u['rol']];
        $_SESSION['pass_default'] = ($password === self::PASSWORD_INICIAL);

        if (password_needs_rehash($u['password'], PASSWORD_DEFAULT)) {
            Usuario::actualizarPassword((int) $u['id'], password_hash($password, PASSWORD_DEFAULT));
        }
        Usuario::registrarLogin((int) $u['id']);
        return [true, ''];
    }

    public static function logout(): void
    {
        unset($_SESSION['user'], $_SESSION['pass_default']);
        session_regenerate_id(true);
    }

    
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

}
