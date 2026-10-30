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
}
