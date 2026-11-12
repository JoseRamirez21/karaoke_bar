<?php
class Reserva
{
    /** Vistas de la lista: próximas (activas), de hoy (día del negocio) o historial. */
    public static function listar(string $vista): array
    {
        $base = "SELECT r.*, m.numero AS mesa, m.capacidad, u.nombre AS registro
                   FROM reservas r JOIN mesas m ON m.id = r.mesa_id JOIN usuarios u ON u.id = r.usuario_id";
        if ($vista === 'hoy') {
            $ini = Estadistica::inicioDiaNegocio();
            return Database::all("$base WHERE r.fecha_hora >= ? AND r.fecha_hora < DATE_ADD(?, INTERVAL 1 DAY) ORDER BY r.fecha_hora", [$ini, $ini]);
        }
        if ($vista === 'historial') {
            return Database::all("$base WHERE NOT (r.estado IN ('pendiente','confirmada') AND r.fecha_hora >= NOW() - INTERVAL 2 HOUR) ORDER BY r.fecha_hora DESC LIMIT 100");
        }
        return Database::all("$base WHERE r.estado IN ('pendiente','confirmada') AND r.fecha_hora >= NOW() - INTERVAL 2 HOUR ORDER BY r.fecha_hora LIMIT 200");
    }

    public static function buscar(int $id): ?array
    {
        return Database::one('SELECT r.*, m.numero AS mesa, m.capacidad FROM reservas r JOIN mesas m ON m.id = r.mesa_id WHERE r.id = ?', [$id]);
    }

    /** ¿Hay otra reserva activa en esa mesa con menos de 2 horas de diferencia? */
    public static function conflicto(int $mesaId, string $fechaHora, int $exceptoId = 0): ?array
    {
        return Database::one(
            "SELECT id, cliente, fecha_hora FROM reservas
              WHERE mesa_id = ? AND estado IN ('pendiente','confirmada') AND id <> ?
                AND fecha_hora > DATE_SUB(?, INTERVAL 2 HOUR) AND fecha_hora < DATE_ADD(?, INTERVAL 2 HOUR) LIMIT 1",
            [$mesaId, $exceptoId, $fechaHora, $fechaHora]
        );
    }

    /** La reserva activa de una mesa que cae en la ventana de "ahora" (1 h antes a 3 h después). */
    public static function paraMesaAhora(int $mesaId): ?array
    {
        return Database::one(
            "SELECT * FROM reservas WHERE mesa_id = ? AND estado IN ('pendiente','confirmada')
                AND fecha_hora BETWEEN (NOW() - INTERVAL 1 HOUR) AND (NOW() + INTERVAL 3 HOUR)
              ORDER BY fecha_hora LIMIT 1",
            [$mesaId]
        );
    }

    public static function crear(array $d, int $usuarioId): int
    {
        Database::run(
            "INSERT INTO reservas (mesa_id, usuario_id, cliente, telefono, personas, fecha_hora, adelanto, notas, estado)
             VALUES (?,?,?,?,?,?,?,?, 'pendiente')",
            [$d['mesa_id'], $usuarioId, $d['cliente'], $d['telefono'] ?: null, $d['personas'], $d['fecha_hora'], $d['adelanto'], $d['notas'] ?: null]
        );
        return Database::lastId();
    }

    public static function actualizar(int $id, array $d): void
    {
        Database::run(
            'UPDATE reservas SET mesa_id = ?, cliente = ?, telefono = ?, personas = ?, fecha_hora = ?, adelanto = ?, notas = ? WHERE id = ?',
            [$d['mesa_id'], $d['cliente'], $d['telefono'] ?: null, $d['personas'], $d['fecha_hora'], $d['adelanto'], $d['notas'] ?: null, $id]
        );
    }

    /** Cambia el estado solo si todavía estaba activa (pendiente o confirmada). */
    public static function cambiarEstado(int $id, string $nuevo): bool
    {
        return Database::run("UPDATE reservas SET estado = ? WHERE id = ? AND estado IN ('pendiente','confirmada')", [$nuevo, $id]) > 0;
    }

    /** Al abrir la mesa, la reserva queda cumplida. */
    public static function cumplir(int $id, int $mesaId): bool
    {
        return Database::run("UPDATE reservas SET estado = 'cumplida' WHERE id = ? AND mesa_id = ? AND estado IN ('pendiente','confirmada')", [$id, $mesaId]) > 0;
    }
}
