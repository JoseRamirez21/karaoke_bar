<?php
/**
 * La cola de canciones del karaoke.
 * Orden de reproducción: primero por "ronda" y luego por hora. La ronda de una canción es su
 * puesto dentro de las canciones de SU mesa (1ª, 2ª, 3ª...), así las mesas se turnan y ninguna
 * acapara la cola. Una canción con ronda 0 es "la siguiente" (la pone el personal).
 */
class Cola
{
    public static function maximoPorMesa(): int
    {
        $n = (int) Config::get('max_canciones_mesa', '3');
        return ($n >= 1 && $n <= 20) ? $n : 3;
    }

    /** Cuántas canciones tiene la mesa en cola (esperando o sonando). */
    public static function usadas(int $sesionId): int
    {
        return (int) Database::value("SELECT COUNT(*) FROM cola_canciones WHERE sesion_mesa_id = ? AND estado IN ('pendiente','reproduciendo')", [$sesionId]);
    }

    /**
     * Agrega una canción a la cola de una mesa abierta. Bloquea la mesa mientras trabaja,
     * para que dos celulares de la misma mesa no se pasen del límite.
     */
    public static function agregar(int $sesionId, string $videoId, string $titulo): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $s = Database::one("SELECT id, mesa_id FROM sesiones_mesa WHERE id = ? AND estado = 'abierta' FOR UPDATE", [$sesionId]);
            if (!$s) throw new AppException('Esta mesa no está abierta. Pídele al personal que la abra.');

            $usadas = self::usadas($sesionId);
            $max    = self::maximoPorMesa();
            if ($usadas >= $max) {
                throw new AppException("Ya tienes $max canciones en la cola. Cuando suene una podrás agregar otra.");
            }
            $repetida = (int) Database::value("SELECT COUNT(*) FROM cola_canciones WHERE video_id = ? AND estado IN ('pendiente','reproduciendo')", [$videoId]);
            if ($repetida > 0) throw new AppException('Esa canción ya está en la cola.');

            Database::run(
                'INSERT INTO cola_canciones (sesion_mesa_id, mesa_id, video_id, titulo, miniatura, ronda) VALUES (?,?,?,?,?,?)',
                [$sesionId, $s['mesa_id'], $videoId, $titulo, Youtube::miniatura($videoId), $usadas + 1]
            );
            $id = Database::lastId();
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** La canción que está sonando ahora (o null). */
    public static function ahora(): ?array
    {
        return Database::one(
            "SELECT c.*, m.numero AS mesa FROM cola_canciones c JOIN mesas m ON m.id = c.mesa_id
              WHERE c.estado = 'reproduciendo' ORDER BY c.id LIMIT 1"
        );
    }

    /** Las que esperan turno, en el orden en que sonarán. */
    public static function pendientes(int $limite = 200): array
    {
        $limite = max(1, $limite);
        return Database::all(
            "SELECT c.id, c.video_id, c.titulo, c.miniatura, c.ronda, c.sesion_mesa_id, m.numero AS mesa
               FROM cola_canciones c JOIN mesas m ON m.id = c.mesa_id
              WHERE c.estado = 'pendiente'
              ORDER BY c.ronda, c.created_at, c.id LIMIT $limite"
        );
    }

    /** El cliente quita su propia canción (solo si aún no suena) o el personal quita cualquiera. */
    public static function quitar(int $id, ?int $sesionId = null): bool
    {
        $sql    = "UPDATE cola_canciones SET estado = 'quitada' WHERE id = ? AND estado = 'pendiente'";
        $params = [$id];
        if ($sesionId !== null) { $sql .= ' AND sesion_mesa_id = ?'; $params[] = $sesionId; }
        return Database::run($sql, $params) > 0;
    }

    /** Salta la que suena ahora o una que espera. */
    public static function saltar(int $id): bool
    {
        return Database::run("UPDATE cola_canciones SET estado = 'omitida', finalizada_en = NOW() WHERE id = ? AND estado IN ('pendiente','reproduciendo')", [$id]) > 0;
    }

    /** La pone de primera en la fila. */
    public static function primero(int $id): bool
    {
        return Database::run("UPDATE cola_canciones SET ronda = 0 WHERE id = ? AND estado = 'pendiente'", [$id]) > 0;
    }

    /**
     * Pasa la siguiente canción a "sonando" (solo si no hay ninguna sonando).
     * Usa un candado para que dos pantallas abiertas no arranquen dos canciones a la vez.
     */
    public static function promover(): ?array
    {
        if ((int) Database::value("SELECT GET_LOCK('karaoke_cola', 3)") !== 1) return self::ahora();
        try {
            $actual = self::ahora();
            if ($actual) return $actual;
            $sig = Database::one("SELECT id FROM cola_canciones WHERE estado = 'pendiente' ORDER BY ronda, created_at, id LIMIT 1");
            if (!$sig) return null;
            Database::run("UPDATE cola_canciones SET estado = 'reproduciendo', iniciada_en = NOW() WHERE id = ? AND estado = 'pendiente'", [$sig['id']]);
            return self::ahora();
        } finally {
            Database::run("DO RELEASE_LOCK('karaoke_cola')");
        }
    }

    /** La pantalla avisa que terminó la canción (o que dio error al reproducirla). */
    public static function terminar(int $id, bool $conError = false): bool
    {
        return Database::run(
            "UPDATE cola_canciones SET estado = ?, finalizada_en = NOW() WHERE id = ? AND estado = 'reproduciendo'",
            [$conError ? 'omitida' : 'reproducida', $id]
        ) > 0;
    }
}