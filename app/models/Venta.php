<?php
class Venta
{
    /** Productos que se pueden vender ahora (producto y categoría activos). */
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

    /** Mesas con cuenta abierta, para elegir a cuál cargar el pedido. */
    public static function mesasAbiertas(): array
    {
        return Database::all(
            "SELECT s.id AS sesion_id, m.numero, s.cliente
               FROM sesiones_mesa s JOIN mesas m ON m.id = s.mesa_id
              WHERE s.estado = 'abierta'
              ORDER BY CAST(m.numero AS UNSIGNED), m.numero"
        );
    }

    /**
     * Registra la venta ya cobrada: guarda el pedido, descuenta el stock y deja el historial,
     * todo en una sola transacción (si algo falla, no se guarda nada).
     * Los precios SIEMPRE se leen de la base de datos, nunca de lo que mande el navegador.
     *
     * @param array<int,int> $items  producto_id => cantidad
     * @return array{id:int, codigo:string, total:float, vuelto:?float, metodo:string}
     */
    public static function crear(int $usuarioId, int $turnoId, ?int $sesionId, int $metodoId, array $items, ?string $recibido): array
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            // Bloquear el turno y la mesa evita vender en un turno que se está cerrando
            // o en una mesa que otro cajero está cerrando en ese mismo instante.
            if (!Database::one("SELECT id FROM turnos_caja WHERE id = ? AND estado = 'abierto' FOR UPDATE", [$turnoId])) {
                throw new AppException('Tu turno ya está cerrado. Abre uno nuevo para vender.');
            }
            if ($sesionId !== null && !Database::one("SELECT id FROM sesiones_mesa WHERE id = ? AND estado = 'abierta' FOR UPDATE", [$sesionId])) {
                throw new AppException('Esa mesa ya fue cerrada. Elige otra o vende en el mostrador.');
            }
            $metodo = Database::one('SELECT id, nombre, es_efectivo FROM metodos_pago WHERE id = ? AND estado = 1', [$metodoId]);
            if (!$metodo) throw new AppException('Elige cómo paga el cliente.');

            ksort($items);   // mismo orden siempre: evita bloqueos cruzados entre dos cajeros
            $lineas = [];
            $total  = 0.0;
            foreach ($items as $pid => $cant) {
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
                $sub     = round((float) $p['precio'] * $cant, 2);
                $total  += $sub;
                $lineas[] = ['p' => $p, 'cant' => $cant, 'sub' => $sub];
            }
            $total = round($total, 2);

            $vuelto = null;
            if ((int) $metodo['es_efectivo'] === 1 && $recibido !== null) {
                if ((float) $recibido + 0.004 < $total) {
                    throw new AppException('El efectivo recibido (' . money($recibido) . ') es menor al total (' . money($total) . ').');
                }
                $vuelto = round((float) $recibido - $total, 2);
            }

            // Se inserta con un código temporal y luego se le pone el definitivo (T-000123) según su id.
            Database::run(
                "INSERT INTO ventas (codigo, sesion_mesa_id, turno_id, usuario_id, metodo_pago_id, total, estado)
                 VALUES (?, ?, ?, ?, ?, ?, 'pagada')",
                ['TMP' . bin2hex(random_bytes(8)), $sesionId, $turnoId, $usuarioId, $metodoId, number_format($total, 2, '.', '')]
            );
            $id     = Database::lastId();
            $codigo = 'T-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
            Database::run('UPDATE ventas SET codigo = ? WHERE id = ?', [$codigo, $id]);

            foreach ($lineas as $l) {
                $p     = $l['p'];
                $nuevo = (int) $p['stock'] - $l['cant'];
                Database::run(
                    'INSERT INTO detalle_venta (venta_id, producto_id, descripcion, cantidad, precio_unitario, subtotal) VALUES (?,?,?,?,?,?)',
                    [$id, $p['id'], $p['nombre'], $l['cant'], $p['precio'], number_format($l['sub'], 2, '.', '')]
                );
                Database::run('UPDATE productos SET stock = ? WHERE id = ?', [$nuevo, $p['id']]);
                Database::run(
                    "INSERT INTO movimientos_stock (producto_id, usuario_id, tipo, cantidad, stock_resultante, motivo) VALUES (?,?,'venta',?,?,?)",
                    [$p['id'], $usuarioId, $l['cant'], $nuevo, 'Venta ' . $codigo]
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
            'SELECT v.*, mp.nombre AS metodo, u.nombre AS cajero, m.numero AS mesa, s.cliente
               FROM ventas v
               JOIN metodos_pago mp ON mp.id = v.metodo_pago_id
               JOIN usuarios u ON u.id = v.usuario_id
               LEFT JOIN sesiones_mesa s ON s.id = v.sesion_mesa_id
               LEFT JOIN mesas m ON m.id = s.mesa_id
              WHERE v.id = ?',
            [$id]
        );
    }

    public static function detalle(int $id): array
    {
        return Database::all('SELECT descripcion, cantidad, precio_unitario, subtotal FROM detalle_venta WHERE venta_id = ? ORDER BY id', [$id]);
    }

    /** Pasa de "pagada" a "entregada". Falla si ya estaba entregada o anulada. */
    public static function entregar(int $id): void
    {
        $n = Database::run("UPDATE ventas SET estado = 'entregada', entregada_en = NOW() WHERE id = ? AND estado = 'pagada'", [$id]);
        if ($n === 0) throw new AppException('Este pedido ya fue entregado o está anulado.');
    }
}