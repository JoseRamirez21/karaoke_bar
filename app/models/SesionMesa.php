<?php
/** La "cuenta" de una mesa: desde que se abre hasta que los clientes se van. */
class SesionMesa
{
    /** La sesión abierta de una mesa (o null si está cerrada). */
    public static function abierta(int $mesaId): ?array
    {
        return Database::one("SELECT * FROM sesiones_mesa WHERE mesa_id = ? AND estado = 'abierta' ORDER BY id DESC LIMIT 1", [$mesaId]);
    }

    public static function buscar(int $id): ?array
    {
        return Database::one(
            'SELECT s.*, m.numero AS mesa, m.capacidad, ua.nombre AS abierta_por_nombre, uc.nombre AS cerrada_por_nombre
               FROM sesiones_mesa s
               JOIN mesas m ON m.id = s.mesa_id
               JOIN usuarios ua ON ua.id = s.abierta_por
               LEFT JOIN usuarios uc ON uc.id = s.cerrada_por
              WHERE s.id = ?',
            [$id]
        );
    }

    /** Abre la mesa: crea la sesión y la marca como ocupada, todo junto o nada. */
    public static function abrir(int $mesaId, int $usuarioId, string $cliente): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $mesa = Database::one('SELECT id, estado FROM mesas WHERE id = ? FOR UPDATE', [$mesaId]);
            if (!$mesa)                         throw new AppException('La mesa no existe.');
            if ($mesa['estado'] === 'inactiva') throw new AppException('Esta mesa está desactivada.');
            if (self::abierta($mesaId))         throw new AppException('Esta mesa ya está abierta.');

            Database::run('INSERT INTO sesiones_mesa (mesa_id, abierta_por, cliente) VALUES (?, ?, ?)',
                [$mesaId, $usuarioId, $cliente !== '' ? $cliente : null]);
            $id = Database::lastId();
            Database::run("UPDATE mesas SET estado = 'ocupada' WHERE id = ?", [$mesaId]);
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Los pedidos (ventas) de la mesa, del más antiguo al más nuevo. */
    public static function ventas(int $sesionId): array
    {
        return Database::all(
            'SELECT v.id, v.codigo, v.total, v.estado, v.created_at, mp.nombre AS metodo, u.nombre AS cajero
               FROM ventas v
               JOIN metodos_pago mp ON mp.id = v.metodo_pago_id
               JOIN usuarios u ON u.id = v.usuario_id
              WHERE v.sesion_mesa_id = ? ORDER BY v.id',
            [$sesionId]
        );
    }

    /** Lo consumido (sin contar anulados) y cuántos pedidos cobrados faltan entregar. */
    public static function resumen(int $sesionId): array
    {
        $r = Database::one(
            "SELECT COALESCE(SUM(CASE WHEN estado <> 'anulada' THEN total END), 0) AS total,
                    COALESCE(SUM(estado <> 'anulada'), 0) AS n,
                    COALESCE(SUM(estado = 'pagada'), 0) AS por_entregar
               FROM ventas WHERE sesion_mesa_id = ?",
            [$sesionId]
        );
        return ['total' => (float) $r['total'], 'n' => (int) $r['n'], 'por_entregar' => (int) $r['por_entregar']];
    }

    /** Cierra la cuenta y libera la mesa. No deja cerrar si hay pedidos cobrados sin entregar. */
    public static function cerrar(int $id, int $usuarioId): array
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $s = Database::one('SELECT * FROM sesiones_mesa WHERE id = ? FOR UPDATE', [$id]);
            if (!$s || $s['estado'] !== 'abierta') throw new AppException('Esta mesa ya fue cerrada.');

            $r = self::resumen($id);
            if ($r['por_entregar'] > 0) {
                $n = $r['por_entregar'];
                throw new AppException("Hay $n pedido" . ($n > 1 ? 's' : '') . ' cobrado' . ($n > 1 ? 's' : '') . ' sin entregar. Entrégalos (o anúlalos) antes de cerrar la mesa.');
            }
            Database::run("UPDATE sesiones_mesa SET estado = 'cerrada', cerrada_en = NOW(), cerrada_por = ? WHERE id = ?", [$usuarioId, $id]);
            Database::run("UPDATE mesas SET estado = 'libre' WHERE id = ?", [$s['mesa_id']]);
            $pdo->commit();
            return ['total' => $r['total']];
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
