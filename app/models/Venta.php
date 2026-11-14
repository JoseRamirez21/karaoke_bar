<?php
class Venta
{
    
    public static function productosVendibles(): array
    {
        return Database::all(
            'SELECT p.id, p.nombre, p.precio, p.stock, c.id AS categoria_id, c.nombre AS categoria
               FROM productos p JOIN categorias c ON c.id = p.categoria_id
              WHERE p.estado = 1 AND c.estado = 1
              ORDER BY c.nombre, p.nombre'
        );
    }

    public static function metodos(): array
    {
        return Database::all('SELECT id, nombre, es_efectivo FROM metodos_pago WHERE estado = 1 ORDER BY id');
    }

    
    public static function mesasAbiertas(): array
    {
        return Database::all(
            "SELECT s.id AS sesion_id, m.numero, s.cliente
               FROM sesiones_mesa s JOIN mesas m ON m.id = s.mesa_id
              WHERE s.estado = 'abierta'
              ORDER BY CAST(m.numero AS UNSIGNED), m.numero"
        );
    }

    
    public static function crear(int $usuarioId, int $turnoId, ?int $sesionId, int $metodoId, array $items, array $combos, ?string $recibido): array
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            
            
            if (!Database::one("SELECT id FROM turnos_caja WHERE id = ? AND estado = 'abierto' FOR UPDATE", [$turnoId])) {
                throw new AppException('Tu turno ya está cerrado. Abre uno nuevo para vender.');
            }
            if ($sesionId !== null && !Database::one("SELECT id FROM sesiones_mesa WHERE id = ? AND estado = 'abierta' FOR UPDATE", [$sesionId])) {
                throw new AppException('Esa mesa ya fue cerrada. Elige otra o vende en el mostrador.');
            }
            $metodo = Database::one('SELECT id, nombre, es_efectivo FROM metodos_pago WHERE id = ? AND estado = 1', [$metodoId]);
            if (!$metodo) throw new AppException('Elige cómo paga el cliente.');

            
            $necesita = [];
            foreach ($items as $pid => $cant) $necesita[(int) $pid] = ($necesita[(int) $pid] ?? 0) + $cant;

            $lineasCombo = [];
            ksort($combos);
            foreach ($combos as $cid => $cant) {
                $co = Database::one(
                    'SELECT c.id, c.nombre, c.precio FROM combos c
                      WHERE c.id = ? AND c.estado = 1 AND (c.vigente_desde IS NULL OR c.vigente_desde <= CURDATE()) AND (c.vigente_hasta IS NULL OR c.vigente_hasta >= CURDATE())',
                    [$cid]
                );
                $comp = $co ? Database::all('SELECT producto_id, cantidad FROM combo_productos WHERE combo_id = ?', [$cid]) : [];
                if (!$co || !$comp) throw new AppException('Una promoción del pedido ya no está disponible. Actualiza la pantalla e inténtalo de nuevo.');
                foreach ($comp as $x) $necesita[(int) $x['producto_id']] = ($necesita[(int) $x['producto_id']] ?? 0) + (int) $x['cantidad'] * $cant;
                $lineasCombo[] = ['combo' => $co, 'cant' => $cant];
            }

            
            ksort($necesita);
            $productos = [];
            foreach ($necesita as $pid => $cant) {
                $p = Database::one(
                    'SELECT p.id, p.nombre, p.precio, p.stock, p.estado, c.estado AS cat_estado
                       FROM productos p JOIN categorias c ON c.id = p.categoria_id
                      WHERE p.id = ? FOR UPDATE',
                    [$pid]
                );
                if (!$p || (int) $p['estado'] !== 1 || (int) $p['cat_estado'] !== 1) {
                    throw new AppException('Un producto del pedido ya no está disponible. Actualiza la pantalla e inténtalo de nuevo.');
                }
                if ($cant > (int) $p['stock']) {
                    throw new AppException('No alcanza el stock de ' . $p['nombre'] . ': quedan ' . (int) $p['stock'] . '.');
                }
                $productos[$pid] = $p;
            }

            
            $lineas = [];
            $total  = 0.0;
            foreach ($items as $pid => $cant) {
                $p = $productos[(int) $pid];
                $sub = round((float) $p['precio'] * $cant, 2);
                $total += $sub;
                $lineas[] = ['producto_id' => (int) $pid, 'combo_id' => null, 'desc' => $p['nombre'], 'cant' => $cant, 'precio' => $p['precio'], 'sub' => $sub];
            }
            foreach ($lineasCombo as $l) {
                $sub = round((float) $l['combo']['precio'] * $l['cant'], 2);
                $total += $sub;
                $lineas[] = ['producto_id' => null, 'combo_id' => (int) $l['combo']['id'], 'desc' => $l['combo']['nombre'], 'cant' => $l['cant'], 'precio' => $l['combo']['precio'], 'sub' => $sub];
            }
            if (!$lineas) throw new AppException('Agrega al menos un producto al pedido.');
            $total = round($total, 2);

            $vuelto = null;
            if ((int) $metodo['es_efectivo'] === 1 && $recibido !== null) {
                if ((float) $recibido + 0.004 < $total) {
                    throw new AppException('El efectivo recibido (' . money($recibido) . ') es menor al total (' . money($total) . ').');
                }
                $vuelto = round((float) $recibido - $total, 2);
            }

            
            Database::run(
                "INSERT INTO ventas (codigo, sesion_mesa_id, turno_id, usuario_id, metodo_pago_id, total, estado)
                 VALUES (?, ?, ?, ?, ?, ?, 'pagada')",
                ['TMP' . bin2hex(random_bytes(8)), $sesionId, $turnoId, $usuarioId, $metodoId, number_format($total, 2, '.', '')]
            );
            $id     = Database::lastId();
            $codigo = 'T-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
            Database::run('UPDATE ventas SET codigo = ? WHERE id = ?', [$codigo, $id]);

            foreach ($lineas as $l) {
                Database::run(
                    'INSERT INTO detalle_venta (venta_id, producto_id, combo_id, descripcion, cantidad, precio_unitario, subtotal) VALUES (?,?,?,?,?,?,?)',
                    [$id, $l['producto_id'], $l['combo_id'], $l['desc'], $l['cant'], $l['precio'], number_format($l['sub'], 2, '.', '')]
                );
            }
            
            foreach ($necesita as $pid => $cant) {
                $nuevo = (int) $productos[$pid]['stock'] - $cant;
                Database::run('UPDATE productos SET stock = ? WHERE id = ?', [$nuevo, $pid]);
                Database::run(
                    "INSERT INTO movimientos_stock (producto_id, usuario_id, tipo, cantidad, stock_resultante, motivo) VALUES (?,?,'venta',?,?,?)",
                    [$pid, $usuarioId, $cant, $nuevo, 'Venta ' . $codigo]
                );
            }
            $pdo->commit();
            return ['id' => $id, 'codigo' => $codigo, 'total' => $total, 'vuelto' => $vuelto, 'metodo' => $metodo['nombre']];
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function buscar(int $id): ?array
    {
        return Database::one(
            'SELECT v.*, mp.nombre AS metodo, u.nombre AS cajero, m.numero AS mesa, s.cliente, ua.nombre AS anulada_por_nombre
               FROM ventas v
               JOIN metodos_pago mp ON mp.id = v.metodo_pago_id
               JOIN usuarios u ON u.id = v.usuario_id
               LEFT JOIN usuarios ua ON ua.id = v.anulada_por
               LEFT JOIN sesiones_mesa s ON s.id = v.sesion_mesa_id
               LEFT JOIN mesas m ON m.id = s.mesa_id
              WHERE v.id = ?',
            [$id]
        );
    }

    public static function detalle(int $id): array
    {
        return Database::all('SELECT descripcion, cantidad, precio_unitario, subtotal, combo_id FROM detalle_venta WHERE venta_id = ? ORDER BY id', [$id]);
    }

    
    public static function entregar(int $id): void
    {
        $n = Database::run("UPDATE ventas SET estado = 'entregada', entregada_en = NOW() WHERE id = ? AND estado = 'pagada'", [$id]);
        if ($n === 0) throw new AppException('Este pedido ya fue entregado o está anulado.');
    }

    
    public static function razonNoAnular(array $venta, ?array $turno, int $usuarioId, bool $esAdmin): ?string
    {
        if ($venta['estado'] === 'anulada')   return 'Este pedido ya está anulado.';
        if ($venta['estado'] === 'entregada') return 'Este pedido ya se entregó, así que no se puede anular.';
        if (!$turno || $turno['estado'] !== 'abierto') return 'El turno de esta venta ya está cerrado: su caja no se puede modificar.';
        if (!$esAdmin && (int) $venta['usuario_id'] !== $usuarioId) return 'Solo quien cobró el pedido o el administrador puede anularlo.';
        return null;
    }

    
    public static function anular(int $id, int $usuarioId, bool $esAdmin, string $motivo): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $previo = Database::one('SELECT turno_id FROM ventas WHERE id = ?', [$id]);
            if (!$previo) throw new AppException('El pedido no existe.');

            
            $turno = Database::one('SELECT id, estado FROM turnos_caja WHERE id = ? FOR UPDATE', [$previo['turno_id']]);
            $v     = Database::one('SELECT * FROM ventas WHERE id = ? FOR UPDATE', [$id]);
            if (!$v) throw new AppException('El pedido no existe.');

            $razon = self::razonNoAnular($v, $turno, $usuarioId, $esAdmin);
            if ($razon !== null) throw new AppException($razon);

            $descontado = Database::all(
                "SELECT producto_id, SUM(cantidad) AS cantidad FROM movimientos_stock WHERE tipo = 'venta' AND motivo = ? GROUP BY producto_id ORDER BY producto_id",
                ['Venta ' . $v['codigo']]
            );
            foreach ($descontado as $l) {
                $p     = Database::one('SELECT stock FROM productos WHERE id = ? FOR UPDATE', [$l['producto_id']]);
                $nuevo = (int) $p['stock'] + (int) $l['cantidad'];
                Database::run('UPDATE productos SET stock = ? WHERE id = ?', [$nuevo, $l['producto_id']]);
                Database::run(
                    "INSERT INTO movimientos_stock (producto_id, usuario_id, tipo, cantidad, stock_resultante, motivo) VALUES (?,?,'anulacion',?,?,?)",
                    [$l['producto_id'], $usuarioId, $l['cantidad'], $nuevo, mb_substr('Anulación ' . $v['codigo'] . ': ' . $motivo, 0, 200)]
                );
            }
            Database::run(
                "UPDATE ventas SET estado = 'anulada', anulada_por = ?, anulada_en = NOW(), motivo_anulacion = ? WHERE id = ?",
                [$usuarioId, $motivo, $id]
            );
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
