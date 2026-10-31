<?php
class Categoria
{
    public static function todas(): array
    {
        return Database::all(
            'SELECT c.id, c.nombre, c.estado, COUNT(p.id) AS productos, COALESCE(SUM(p.estado = 1), 0) AS activos
               FROM categorias c LEFT JOIN productos p ON p.categoria_id = c.id
              GROUP BY c.id, c.nombre, c.estado
              ORDER BY c.estado DESC, c.nombre'
        );
    }

    public static function activas(): array
    {
        return Database::all('SELECT id, nombre FROM categorias WHERE estado = 1 ORDER BY nombre');
    }

    public static function buscar(int $id): ?array
    {
        return Database::one('SELECT * FROM categorias WHERE id = ?', [$id]);
    }

    public static function nombreEnUso(string $nombre, int $exceptoId = 0): bool
    {
        return (int) Database::value('SELECT COUNT(*) FROM categorias WHERE nombre = ? AND id <> ?', [$nombre, $exceptoId]) > 0;
    }

    public static function crear(string $nombre): int
    {
        Database::run('INSERT INTO categorias (nombre) VALUES (?)', [$nombre]);
        return Database::lastId();
    }

    public static function renombrar(int $id, string $nombre): void
    {
        Database::run('UPDATE categorias SET nombre = ? WHERE id = ?', [$nombre, $id]);
    }

    public static function setEstado(int $id, int $estado): void
    {
        Database::run('UPDATE categorias SET estado = ? WHERE id = ?', [$estado, $id]);
    }
}
