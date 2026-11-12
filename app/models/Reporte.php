<?php
/** Consultas de solo lectura para los reportes. El "día" del bar empieza a las DIA_INICIO_HORA (por las noches que pasan de las 12). */
class Reporte
{
    /** @return array{0:string,1:string} [inicio, fin) del rango de días del negocio */
    public static function rango(string $desde, string $hasta): array
    {
        $h = sprintf(' %02d:00:00', DIA_INICIO_HORA);
        return [$desde . $h, date('Y-m-d', strtotime($hasta . ' +1 day')) . $h];
    }

    public static function resumen(string $ini, string $fin): array
    {
        $r = Database::one(
            "SELECT COALESCE(SUM(CASE WHEN estado <> 'anulada' THEN total END), 0) AS total,
                    COALESCE(SUM(estado <> 'anulada'), 0) AS n,
                    COALESCE(SUM(estado = 'anulada'), 0) AS anuladas_n,
                    COALESCE(SUM(CASE WHEN estado = 'anulada' THEN total END), 0) AS anuladas_total,
                    COALESCE(SUM(estado = 'pagada'), 0) AS por_entregar
               FROM ventas WHERE created_at >= ? AND created_at < ?",
            [$ini, $fin]
        );
        $r = array_map('floatval', $r);
        $r['ticket'] = $r['n'] > 0 ? $r['total'] / $r['n'] : 0.0;
        $r['canciones'] = (int) Database::value("SELECT COUNT(*) FROM cola_canciones WHERE estado = 'reproducida' AND finalizada_en >= ? AND finalizada_en < ?", [$ini, $fin]);
        return $r;
    }

    public static function porDia(string $ini, string $fin): array
    {
        return Database::all(
            "SELECT DATE(DATE_SUB(created_at, INTERVAL " . (int) DIA_INICIO_HORA . " HOUR)) AS dia, SUM(total) AS total, COUNT(*) AS n
               FROM ventas WHERE estado <> 'anulada' AND created_at >= ? AND created_at < ? GROUP BY dia ORDER BY dia",
            [$ini, $fin]
        );
    }

    public static function porMetodo(string $ini, string $fin): array
    {
        return Database::all(
            "SELECT mp.nombre, COALESCE(SUM(v.total), 0) AS total, COUNT(v.id) AS n
               FROM metodos_pago mp
               LEFT JOIN ventas v ON v.metodo_pago_id = mp.id AND v.estado <> 'anulada' AND v.created_at >= ? AND v.created_at < ?
              WHERE mp.estado = 1 GROUP BY mp.id, mp.nombre ORDER BY mp.id",
            [$ini, $fin]
        );
    }

    public static function topProductos(string $ini, string $fin, int $limite = 15): array
    {
        $limite = max(1, $limite);
        return Database::all(
            "SELECT d.descripcion, SUM(d.cantidad) AS cantidad, SUM(d.subtotal) AS total, MAX(d.combo_id IS NOT NULL) AS es_combo
               FROM detalle_venta d JOIN ventas v ON v.id = d.venta_id
              WHERE v.estado <> 'anulada' AND v.created_at >= ? AND v.created_at < ?
              GROUP BY d.descripcion ORDER BY cantidad DESC, total DESC LIMIT $limite",
            [$ini, $fin]
        );
    }

    public static function porCajero(string $ini, string $fin): array
    {
        return Database::all(
            "SELECT u.nombre, COUNT(*) AS n, SUM(v.total) AS total
               FROM ventas v JOIN usuarios u ON u.id = v.usuario_id
              WHERE v.estado <> 'anulada' AND v.created_at >= ? AND v.created_at < ?
              GROUP BY u.id, u.nombre ORDER BY total DESC",
            [$ini, $fin]
        );
    }

    /** Ventas por hora del reloj, ordenadas como corre la noche (18, 19 ... 23, 0, 1 ...). */
    public static function porHora(string $ini, string $fin): array
    {
        $filas = Database::all(
            "SELECT HOUR(created_at) AS h, SUM(total) AS total, COUNT(*) AS n
               FROM ventas WHERE estado <> 'anulada' AND created_at >= ? AND created_at < ? GROUP BY h",
            [$ini, $fin]
        );
        usort($filas, fn($a, $b) => (($a['h'] - DIA_INICIO_HORA + 24) % 24) <=> (($b['h'] - DIA_INICIO_HORA + 24) % 24));
        return $filas;
    }

    public static function turnos(string $ini, string $fin): array
    {
        return Database::all(
            "SELECT t.id, t.abierto_en, t.cerrado_en, t.monto_inicial, t.efectivo_esperado, t.efectivo_contado, t.diferencia, t.estado, t.observacion, u.nombre AS cajero
               FROM turnos_caja t JOIN usuarios u ON u.id = t.usuario_id
              WHERE t.abierto_en >= ? AND t.abierto_en < ? ORDER BY t.id DESC",
            [$ini, $fin]
        );
    }

    public static function ventasDetalle(string $ini, string $fin): array
    {
        return Database::all(
            "SELECT v.codigo, v.created_at, COALESCE(m.numero, 'Mostrador') AS lugar, u.nombre AS cajero, mp.nombre AS metodo, v.estado, v.total
               FROM ventas v
               JOIN usuarios u ON u.id = v.usuario_id
               JOIN metodos_pago mp ON mp.id = v.metodo_pago_id
               LEFT JOIN sesiones_mesa s ON s.id = v.sesion_mesa_id
               LEFT JOIN mesas m ON m.id = s.mesa_id
              WHERE v.created_at >= ? AND v.created_at < ? ORDER BY v.id",
            [$ini, $fin]
        );
    }
}
