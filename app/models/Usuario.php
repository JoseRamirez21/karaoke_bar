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
}
