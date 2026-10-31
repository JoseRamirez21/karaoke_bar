<?php
class Usuario
{
    public static function buscarPorUsuario(string $usuario): ?array
    {
        return Database::one(
            'SELECT u.*, r.nombre AS rol FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE u.usuario = ? LIMIT 1',
            [$usuario]
        );
    }

    public static function buscarPorId(int $id): ?array
    {
        return Database::one(
            'SELECT u.*, r.nombre AS rol FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE u.id = ? LIMIT 1',
            [$id]
        );
    }

    public static function actualizarPassword(int $id, string $hash): void
    {
        Database::run('UPDATE usuarios SET password = ? WHERE id = ?', [$hash, $id]);
    }

    public static function registrarLogin(int $id): void
    {
        Database::run('UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?', [$id]);
    }

    public static function todos(): array
    {
        return Database::all(
            'SELECT u.id, u.nombre, u.usuario, u.telefono, u.estado, u.ultimo_login, r.nombre AS rol
               FROM usuarios u JOIN roles r ON r.id = u.rol_id
              ORDER BY u.estado DESC, r.id, u.nombre'
        );
    }

    public static function roles(): array
    {
        return Database::all('SELECT id, nombre FROM roles ORDER BY id');
    }

    public static function usuarioEnUso(string $usuario, int $exceptoId = 0): bool
    {
        return (int) Database::value('SELECT COUNT(*) FROM usuarios WHERE usuario = ? AND id <> ?', [$usuario, $exceptoId]) > 0;
    }

    public static function crear(array $d): int
    {
        Database::run(
            'INSERT INTO usuarios (rol_id, nombre, usuario, password, telefono) VALUES (?,?,?,?,?)',
            [$d['rol_id'], $d['nombre'], $d['usuario'], $d['password'], $d['telefono'] ?: null]
        );
        return Database::lastId();
    }

    public static function actualizar(int $id, array $d): void
    {
        Database::run(
            'UPDATE usuarios SET rol_id = ?, nombre = ?, usuario = ?, telefono = ? WHERE id = ?',
            [$d['rol_id'], $d['nombre'], $d['usuario'], $d['telefono'] ?: null, $id]
        );
    }

    public static function setEstado(int $id, int $estado): void
    {
        Database::run('UPDATE usuarios SET estado = ? WHERE id = ?', [$estado, $id]);
    }
}
