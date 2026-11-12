<?php
/** Auditoría: nunca debe romper el flujo principal. */
class Bitacora
{
    public static function log(string $accion, ?string $entidad = null, ?int $entidadId = null, ?string $detalle = null, ?int $usuarioId = null): void
    {
        try {
            Database::run(
                'INSERT INTO bitacora (usuario_id, accion, entidad, entidad_id, detalle, ip) VALUES (?,?,?,?,?,?)',
                [$usuarioId ?? (Auth::check() ? Auth::id() : null), $accion, $entidad, $entidadId,
                 $detalle !== null ? mb_substr($detalle, 0, 500) : null, $_SERVER['REMOTE_ADDR'] ?? null]
            );
        } catch (Throwable $e) {
            error_log('Bitacora: ' . $e->getMessage());
        }
    }

    /**
     * Lista la bitácora con filtros. $f puede traer: accion, usuario_id, desde, hasta (Y-m-d) y q (texto del detalle).
     * @return array{filas:array,total:int}
     */
    public static function listar(array $f, int $pagina, int $porPagina = 50): array
    {
        $donde = []; $par = [];
        if (!empty($f['accion']))     { $donde[] = 'b.accion = ?';     $par[] = $f['accion']; }
        if (!empty($f['usuario_id'])) { $donde[] = 'b.usuario_id = ?'; $par[] = (int) $f['usuario_id']; }
        if (!empty($f['desde']))      { $donde[] = 'b.created_at >= ?'; $par[] = $f['desde'] . ' 00:00:00'; }
        if (!empty($f['hasta']))      { $donde[] = 'b.created_at < DATE_ADD(?, INTERVAL 1 DAY)'; $par[] = $f['hasta'] . ' 00:00:00'; }
        if (!empty($f['q']))          { $donde[] = 'b.detalle LIKE ?'; $par[] = '%' . addcslashes($f['q'], '%_\\') . '%'; }
        $w = $donde ? 'WHERE ' . implode(' AND ', $donde) : '';

        $total  = (int) Database::value("SELECT COUNT(*) FROM bitacora b $w", $par);
        $limite = max(1, $porPagina);
        $inicio = max(0, ($pagina - 1) * $limite);
        $filas  = Database::all(
            "SELECT b.id, b.accion, b.entidad, b.entidad_id, b.detalle, b.ip, b.created_at, u.nombre AS usuario
               FROM bitacora b LEFT JOIN usuarios u ON u.id = b.usuario_id
               $w ORDER BY b.id DESC LIMIT $limite OFFSET $inicio",
            $par
        );
        return ['filas' => $filas, 'total' => $total];
    }

    public static function acciones(): array
    {
        return array_column(Database::all('SELECT DISTINCT accion FROM bitacora ORDER BY accion'), 'accion');
    }
}
