<?php
class Mesa
{
    public static function todas(bool $incluirInactivas = true): array
    {
        $where = $incluirInactivas ? '' : "WHERE m.estado <> 'inactiva'";
        return Database::all(
            "SELECT m.id, m.numero, m.capacidad, m.estado,
                    s.id AS sesion_id, s.abierta_en, s.cliente
               FROM mesas m
               LEFT JOIN sesiones_mesa s ON s.mesa_id = m.id AND s.estado = 'abierta'
               $where
              ORDER BY CAST(m.numero AS UNSIGNED), m.numero"
        );
    }

    public static function buscar(int $id): ?array
    {
        return Database::one('SELECT * FROM mesas WHERE id = ?', [$id]);
    }

    public static function numeroEnUso(string $numero, int $exceptoId = 0): bool
    {
        return (int) Database::value('SELECT COUNT(*) FROM mesas WHERE numero = ? AND id <> ?', [$numero, $exceptoId]) > 0;
    }

    public static function crear(string $numero, int $capacidad): int
    {
        Database::run('INSERT INTO mesas (numero, capacidad, estado) VALUES (?,?,?)', [$numero, $capacidad, 'libre']);
        return Database::lastId();
    }

    public static function actualizar(int $id, string $numero, int $capacidad): void
    {
        Database::run('UPDATE mesas SET numero = ?, capacidad = ? WHERE id = ?', [$numero, $capacidad, $id]);
    }

    public static function setEstado(int $id, string $estado): void
    {
        Database::run('UPDATE mesas SET estado = ? WHERE id = ?', [$estado, $id]);
    }

    public static function tieneSesionAbierta(int $id): bool
    {
        return (int) Database::value("SELECT COUNT(*) FROM sesiones_mesa WHERE mesa_id = ? AND estado = 'abierta'", [$id]) > 0;
    }
}
