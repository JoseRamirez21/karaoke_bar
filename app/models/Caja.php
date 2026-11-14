<?php
class Caja
{
    
    public static function abierto(int $usuarioId): ?array
    {
        return Database::one("SELECT * FROM turnos_caja WHERE usuario_id = ? AND estado = 'abierto' ORDER BY id DESC LIMIT 1", [$usuarioId]);
    }

    public static function buscar(int $id): ?array
    {
        return Database::one(
            'SELECT t.*, u.nombre AS cajero FROM turnos_caja t JOIN usuarios u ON u.id = t.usuario_id WHERE t.id = ?',
            [$id]
        );
    }

    
    public static function abrir(int $usuarioId, string $montoInicial): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            Database::one('SELECT id FROM usuarios WHERE id = ? FOR UPDATE', [$usuarioId]);
            if (self::abierto($usuarioId)) {
                throw new AppException('Ya tienes un turno abierto.');
            }
            Database::run('INSERT INTO turnos_caja (usuario_id, monto_inicial) VALUES (?, ?)', [$usuarioId, $montoInicial]);
            $id = Database::lastId();
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    
    public static function resumen(int $turnoId): array
    {
        $metodos = Database::all(
            "SELECT mp.nombre, mp.es_efectivo, COALESCE(SUM(v.total), 0) AS total, COUNT(v.id) AS n
               FROM metodos_pago mp
               LEFT JOIN ventas v ON v.metodo_pago_id = mp.id AND v.turno_id = ? AND v.estado <> 'anulada'
              WHERE mp.estado = 1
              GROUP BY mp.id, mp.nombre, mp.es_efectivo
              ORDER BY mp.id",
            [$turnoId]
        );
        $total = 0.0; $n = 0; $efectivo = 0.0;
        foreach ($metodos as $m) {
            $total += (float) $m['total'];
            $n     += (int) $m['n'];
            if ((int) $m['es_efectivo'] === 1) $efectivo += (float) $m['total'];
        }
        $anuladas = Database::one("SELECT COUNT(*) AS n, COALESCE(SUM(total), 0) AS total FROM ventas WHERE turno_id = ? AND estado = 'anulada'", [$turnoId]);
        $porEntregar = (int) Database::value("SELECT COUNT(*) FROM ventas WHERE turno_id = ? AND estado = 'pagada'", [$turnoId]);

        return ['metodos' => $metodos, 'total' => $total, 'n' => $n, 'efectivo' => $efectivo,
                'anuladas' => $anuladas, 'por_entregar' => $porEntregar];
    }

    
    public static function cerrar(int $id, string $contado, string $observacion): array
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $t = Database::one('SELECT * FROM turnos_caja WHERE id = ? FOR UPDATE', [$id]);
            if (!$t || $t['estado'] !== 'abierto') {
                throw new AppException('Este turno ya está cerrado.');
            }
            $r          = self::resumen($id);
            $esperado   = round((float) $t['monto_inicial'] + $r['efectivo'], 2);
            $diferencia = round((float) $contado - $esperado, 2);

            if (abs($diferencia) >= 0.005 && mb_strlen($observacion) < 3) {
                $texto = $diferencia < 0 ? 'faltan ' : 'sobran ';
                throw new AppException('El efectivo contado no coincide con lo esperado (' . $texto . money(abs($diferencia)) . '). Escribe una explicación para poder cerrar.');
            }
            Database::run(
                "UPDATE turnos_caja
                    SET cerrado_en = NOW(), efectivo_esperado = ?, efectivo_contado = ?, diferencia = ?, observacion = ?, estado = 'cerrado'
                  WHERE id = ?",
                [$esperado, $contado, $diferencia, $observacion !== '' ? mb_substr($observacion, 0, 255) : null, $id]
            );
            $pdo->commit();
            return ['esperado' => $esperado, 'diferencia' => $diferencia];
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    
    public static function historial(?int $usuarioId, int $limite = 15): array
    {
        $limite = max(1, $limite);
        $where  = $usuarioId !== null ? 'WHERE t.usuario_id = ?' : '';
        return Database::all(
            "SELECT t.id, t.abierto_en, t.cerrado_en, t.monto_inicial, t.efectivo_esperado, t.efectivo_contado,
                    t.diferencia, t.estado, u.nombre AS cajero
               FROM turnos_caja t JOIN usuarios u ON u.id = t.usuario_id
               $where ORDER BY t.id DESC LIMIT $limite",
            $usuarioId !== null ? [$usuarioId] : []
        );
    }
}
