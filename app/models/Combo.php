<?php
class Combo
{
    private const VIGENTE = "c.estado = 1 AND (c.vigente_desde IS NULL OR c.vigente_desde <= CURDATE()) AND (c.vigente_hasta IS NULL OR c.vigente_hasta >= CURDATE())";

    public static function todos(): array
    {
        return Database::all(
            'SELECT c.*,
                    (SELECT COALESCE(SUM(p.precio * cp.cantidad), 0) FROM combo_productos cp JOIN productos p ON p.id = cp.producto_id WHERE cp.combo_id = c.id) AS precio_normal
               FROM combos c ORDER BY c.estado DESC, c.nombre'
        );
    }

    /** combo_id => "6 x Cerveza, 1 x Piqueo" */
    public static function resumenes(): array
    {
        $filas = Database::all(
            "SELECT cp.combo_id, GROUP_CONCAT(CONCAT(cp.cantidad, ' x ', p.nombre) ORDER BY p.nombre SEPARATOR ', ') AS texto
               FROM combo_productos cp JOIN productos p ON p.id = cp.producto_id GROUP BY cp.combo_id"
        );
        return array_column($filas, 'texto', 'combo_id');
    }

    public static function buscar(int $id): ?array
    {
        return Database::one('SELECT * FROM combos WHERE id = ?', [$id]);
    }

    /** producto_id => cantidad */
    public static function componentes(int $id): array
    {
        $filas = Database::all('SELECT producto_id, cantidad FROM combo_productos WHERE combo_id = ?', [$id]);
        return array_map('intval', array_column($filas, 'cantidad', 'producto_id'));
    }

    public static function nombreEnUso(string $nombre, int $exceptoId = 0): bool
    {
        return (int) Database::value('SELECT COUNT(*) FROM combos WHERE nombre = ? AND id <> ?', [$nombre, $exceptoId]) > 0;
    }

    /** Crea o actualiza el combo y reemplaza sus productos, todo junto o nada. */
    public static function guardar(?int $id, array $d, array $componentes): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            if ($id === null) {
                Database::run('INSERT INTO combos (nombre, descripcion, precio, vigente_desde, vigente_hasta, estado) VALUES (?,?,?,?,?,?)',
                    [$d['nombre'], $d['descripcion'] ?: null, $d['precio'], $d['desde'] ?: null, $d['hasta'] ?: null, $d['estado']]);
                $id = Database::lastId();
            } else {
                Database::run('UPDATE combos SET nombre = ?, descripcion = ?, precio = ?, vigente_desde = ?, vigente_hasta = ?, estado = ? WHERE id = ?',
                    [$d['nombre'], $d['descripcion'] ?: null, $d['precio'], $d['desde'] ?: null, $d['hasta'] ?: null, $d['estado'], $id]);
                Database::run('DELETE FROM combo_productos WHERE combo_id = ?', [$id]);
            }
            foreach ($componentes as $pid => $cant) {
                Database::run('INSERT INTO combo_productos (combo_id, producto_id, cantidad) VALUES (?,?,?)', [$id, $pid, $cant]);
            }
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function setEstado(int $id, int $estado): void
    {
        Database::run('UPDATE combos SET estado = ? WHERE id = ?', [$estado, $id]);
    }

    /**
     * Combos que se pueden vender hoy: activos, dentro de su vigencia y con todos sus productos activos.
     * "disponibles" = cuántos se pueden armar con el stock actual.
     */
    public static function vendibles(): array
    {
        $combos = Database::all('SELECT c.id, c.nombre, c.descripcion, c.precio FROM combos c WHERE ' . self::VIGENTE . ' ORDER BY c.nombre');
        $res = [];
        foreach ($combos as $c) {
            $comp = Database::all(
                'SELECT cp.cantidad, p.nombre, p.stock, p.estado, cat.estado AS cat_estado
                   FROM combo_productos cp JOIN productos p ON p.id = cp.producto_id JOIN categorias cat ON cat.id = p.categoria_id
                  WHERE cp.combo_id = ?', [$c['id']]
            );
            if (!$comp) continue;
            $disp = PHP_INT_MAX; $texto = []; $activo = true;
            foreach ($comp as $x) {
                if ((int) $x['estado'] !== 1 || (int) $x['cat_estado'] !== 1) $activo = false;
                $disp    = min($disp, intdiv(max(0, (int) $x['stock']), max(1, (int) $x['cantidad'])));
                $texto[] = (int) $x['cantidad'] . ' x ' . $x['nombre'];
            }
            if (!$activo) continue;
            $c['disponibles'] = $disp;
            $c['incluye']     = implode(', ', $texto);
            $res[] = $c;
        }
        return $res;
    }
}
