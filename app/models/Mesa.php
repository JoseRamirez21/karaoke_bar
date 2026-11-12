<?php
class Mesa
{
    /**
     * Estado que se muestra: una mesa libre aparece "reservada" si tiene una reserva pendiente o confirmada
     * desde 1 hora antes hasta 3 horas después de ahora. (El estado guardado en la base solo importa para
     * ocupada e inactiva; una "reservada" vieja guardada a mano se trata como libre.)
     */
    public const SQL_ESTADO = "CASE
        WHEN m.estado IN ('libre','reservada') AND EXISTS (
            SELECT 1 FROM reservas r WHERE r.mesa_id = m.id AND r.estado IN ('pendiente','confirmada')
               AND r.fecha_hora BETWEEN (NOW() - INTERVAL 1 HOUR) AND (NOW() + INTERVAL 3 HOUR)
        ) THEN 'reservada'
        WHEN m.estado = 'reservada' THEN 'libre'
        ELSE m.estado END";

    public static function todas(bool $incluirInactivas = true): array
    {
        $where = $incluirInactivas ? '' : "WHERE m.estado <> 'inactiva'";
        return Database::all(
            "SELECT m.id, m.numero, m.capacidad, m.qr_token, " . self::SQL_ESTADO . " AS estado,
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

    /** La columna qr_token sigue en la base de datos (es obligatoria) pero ya no se usa para nada visible. */
    public static function nuevoToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    public static function crear(string $numero, int $capacidad): int
    {
        Database::run('INSERT INTO mesas (numero, capacidad, estado, qr_token) VALUES (?,?,?,?)', [$numero, $capacidad, 'libre', self::nuevoToken()]);
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
