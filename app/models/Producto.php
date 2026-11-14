<?php
class Producto
{
    public static function todos(): array
    {
        return Database::all(
            'SELECT p.*, c.nombre AS categoria
               FROM productos p JOIN categorias c ON c.id = p.categoria_id
              ORDER BY p.estado DESC, c.nombre, p.nombre'
        );
    }

    public static function buscar(int $id): ?array
    {
        return Database::one('SELECT p.*, c.nombre AS categoria FROM productos p JOIN categorias c ON c.id = p.categoria_id WHERE p.id = ?', [$id]);
    }

    public static function nombreEnUso(string $nombre, int $exceptoId = 0): bool
    {
        return (int) Database::value('SELECT COUNT(*) FROM productos WHERE nombre = ? AND id <> ?', [$nombre, $exceptoId]) > 0;
    }

    
    public static function crear(array $d, int $stockInicial, int $usuarioId): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            Database::run(
                'INSERT INTO productos (categoria_id, nombre, precio, stock, stock_minimo, estado) VALUES (?,?,?,?,?,?)',
                [$d['categoria_id'], $d['nombre'], $d['precio'], $stockInicial, $d['stock_minimo'], $d['estado']]
            );
            $id = Database::lastId();
            if ($stockInicial > 0) {
                self::movimiento($id, $usuarioId, 'entrada', $stockInicial, $stockInicial, 'Stock inicial');
            }
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function actualizar(int $id, array $d): void
    {
        Database::run(
            'UPDATE productos SET categoria_id = ?, nombre = ?, precio = ?, stock_minimo = ?, estado = ? WHERE id = ?',
            [$d['categoria_id'], $d['nombre'], $d['precio'], $d['stock_minimo'], $d['estado'], $id]
        );
    }

    public static function setEstado(int $id, int $estado): void
    {
        Database::run('UPDATE productos SET estado = ? WHERE id = ?', [$estado, $id]);
    }

    
    public static function ajustarStock(int $id, string $tipo, int $cantidad, string $motivo, int $usuarioId): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $fila = Database::one('SELECT stock FROM productos WHERE id = ? FOR UPDATE', [$id]);
            if (!$fila) throw new AppException('El producto no existe.');
            $actual = (int) $fila['stock'];

            switch ($tipo) {
                case 'entrada': $nuevo = $actual + $cantidad; break;
                case 'salida':
                    if ($cantidad > $actual) throw new AppException("No puedes sacar $cantidad: solo hay $actual en stock.");
                    $nuevo = $actual - $cantidad;
                    break;
                case 'ajuste':
                    if ($cantidad === $actual) throw new AppException("El stock ya es $actual, no hay nada que ajustar.");
                    $nuevo = $cantidad;
                    break;
                default: throw new AppException('Tipo de movimiento no válido.');
            }
            $mov = $tipo === 'ajuste' ? abs($nuevo - $actual) : $cantidad;
            if ($tipo === 'ajuste') $motivo = trim($motivo . " (de $actual a $nuevo)");

            Database::run('UPDATE productos SET stock = ? WHERE id = ?', [$nuevo, $id]);
            self::movimiento($id, $usuarioId, $tipo, $mov, $nuevo, $motivo);
            $pdo->commit();
            return $nuevo;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function movimiento(int $productoId, int $usuarioId, string $tipo, int $cantidad, int $resultante, string $motivo): void
    {
        Database::run(
            'INSERT INTO movimientos_stock (producto_id, usuario_id, tipo, cantidad, stock_resultante, motivo) VALUES (?,?,?,?,?,?)',
            [$productoId, $usuarioId, $tipo, $cantidad, $resultante, $motivo !== '' ? mb_substr($motivo, 0, 200) : null]
        );
    }

    public static function movimientos(int $productoId, int $limite = 10): array
    {
        $limite = max(1, $limite);
        return Database::all(
            "SELECT m.tipo, m.cantidad, m.stock_resultante, m.motivo, m.created_at, u.nombre AS usuario
               FROM movimientos_stock m JOIN usuarios u ON u.id = m.usuario_id
              WHERE m.producto_id = ? ORDER BY m.id DESC LIMIT $limite",
            [$productoId]
        );
    }
}
