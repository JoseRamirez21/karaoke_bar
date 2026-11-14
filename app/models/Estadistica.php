<?php

class Estadistica
{
    
    public static function inicioDiaNegocio(): string
    {
        return date('Y-m-d', strtotime('-' . DIA_INICIO_HORA . ' hours')) . sprintf(' %02d:00:00', DIA_INICIO_HORA);
    }

    public static function turnoAbierto(int $usuarioId): ?array
    {
        return Database::one("SELECT * FROM turnos_caja WHERE usuario_id = ? AND estado = 'abierto' ORDER BY id DESC LIMIT 1", [$usuarioId]);
    }

    public static function mesas(): array
    {
        return Database::all("SELECT m.id, m.numero, m.capacidad, m.estado FROM mesas m WHERE m.estado <> 'inactiva' ORDER BY CAST(m.numero AS UNSIGNED), m.numero");
    }

    
    public static function ventas(string $desde, ?int $turnoId = null): array
    {
        $cond  = $turnoId !== null ? 'v.turno_id = ?' : 'v.created_at >= ?';
        $param = $turnoId ?? $desde;
        $metodos = Database::all(
            "SELECT mp.id, mp.nombre, COALESCE(SUM(v.total), 0) AS total, COUNT(v.id) AS n
               FROM metodos_pago mp
               LEFT JOIN ventas v ON v.metodo_pago_id = mp.id AND v.estado <> 'anulada' AND $cond
              WHERE mp.estado = 1
              GROUP BY mp.id, mp.nombre
              ORDER BY mp.id",
            [$param]
        );
        $total = 0.0; $n = 0;
        foreach ($metodos as $m) { $total += (float) $m['total']; $n += (int) $m['n']; }
        return ['total' => $total, 'n' => $n, 'metodos' => $metodos];
    }

    public static function stockBajo(int $limite = 6): array
    {
        $limite = max(1, $limite);
        return Database::all("SELECT nombre, stock, stock_minimo FROM v_stock_bajo ORDER BY stock ASC, nombre LIMIT $limite");
    }

    public static function stockBajoTotal(): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM v_stock_bajo');
    }

}
