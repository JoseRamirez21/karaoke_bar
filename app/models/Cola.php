<?php
/**
 * La cola de karaoke, que maneja SOLO el personal (admin o cajero): reemplaza al papel donde antes se anotaban las canciones.
 *
 * Orden: primero por "ronda" y luego por hora de anotación. La ronda de una canción es su puesto dentro de las
 * canciones de SU mesa (1ª, 2ª, 3ª...), así las mesas se turnan y ninguna acapara la fila.
 * Una canción con ronda 0 es "la siguiente" (la pone el personal con el botón "Primero").
 * Estados: pendiente (esperando) -> reproduciendo (cantando ahora) -> reproducida (ya cantó); también omitida y quitada.
 */
class Cola
{
    public static function maximoPorMesa(): int
    {
        $n = (int) Config::get('max_canciones_mesa', '3');
        return ($n >= 1 && $n <= 50) ? $n : 3;
    }

    /** Cuántas canciones tiene la mesa en la fila (esperando o cantando). */
    public static function usadas(int $sesionId): int
    {
        return (int) Database::value("SELECT COUNT(*) FROM cola_canciones WHERE sesion_mesa_id = ? AND estado IN ('pendiente','reproduciendo')", [$sesionId]);
    }

    /** Anota una canción para una mesa abierta. Bloquea la mesa mientras trabaja para respetar el límite. */
    public static function agregar(int $sesionId, string $titulo): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $s = Database::one(
                "SELECT s.id, s.mesa_id, m.numero FROM sesiones_mesa s JOIN mesas m ON m.id = s.mesa_id
                  WHERE s.id = ? AND s.estado = 'abierta' FOR UPDATE", [$sesionId]);
            if (!$s) throw new AppException('Esa mesa no está abierta. Ábrela primero en Salón y mesas.');

            $usadas = self::usadas($sesionId);
            $max    = self::maximoPorMesa();
            if ($usadas >= $max) {
                throw new AppException('La mesa ' . $s['numero'] . " ya tiene $max canciones en la fila. Cuando cante una, podrás anotar otra (el límite se cambia en Configuración).");
            }
            $repetida = (int) Database::value("SELECT COUNT(*) FROM cola_canciones WHERE LOWER(titulo) = LOWER(?) AND estado IN ('pendiente','reproduciendo')", [$titulo]);
            if ($repetida > 0) throw new AppException('Esa canción ya está en la fila.');

            Database::run(
                "INSERT INTO cola_canciones (sesion_mesa_id, mesa_id, video_id, titulo, ronda) VALUES (?,?,'',?,?)",
                [$sesionId, $s['mesa_id'], $titulo, $usadas + 1]
            );
            $id = Database::lastId();
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** La que está cantando ahora (o null). */
    public static function ahora(): ?array
    {
        return Database::one(
            "SELECT c.*, m.numero AS mesa FROM cola_canciones c JOIN mesas m ON m.id = c.mesa_id
              WHERE c.estado = 'reproduciendo' ORDER BY c.id LIMIT 1"
        );
    }

    /** Las que esperan turno, en el orden en que cantarán. */
    public static function pendientes(int $limite = 200): array
    {
        $limite = max(1, $limite);
        return Database::all(
            "SELECT c.id, c.titulo, c.ronda, c.sesion_mesa_id, m.numero AS mesa
               FROM cola_canciones c JOIN mesas m ON m.id = c.mesa_id
              WHERE c.estado = 'pendiente'
              ORDER BY c.ronda, c.created_at, c.id LIMIT $limite"
        );
    }

    /** Las últimas que ya cantaron (para llevar el control de la noche). */
    public static function recientes(int $limite = 8): array
    {
        $limite = max(1, $limite);
        return Database::all(
            "SELECT c.titulo, c.finalizada_en, m.numero AS mesa
               FROM cola_canciones c JOIN mesas m ON m.id = c.mesa_id
              WHERE c.estado = 'reproducida' ORDER BY c.finalizada_en DESC, c.id DESC LIMIT $limite"
        );
    }

    public static function quitar(int $id): bool
    {
        return Database::run("UPDATE cola_canciones SET estado = 'quitada' WHERE id = ? AND estado = 'pendiente'", [$id]) > 0;
    }

    /** Salta la que está cantando o una que espera (queda como "omitida"). */
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
     * "Siguiente": da por terminada la que cantaba y pone a cantar la próxima de la fila.
     * Usa un candado para que dos personas pulsando a la vez no salten dos canciones.
     */
    public static function avanzar(): ?array
    {
        if ((int) Database::value("SELECT GET_LOCK('karaoke_cola', 3)") !== 1) return self::ahora();
        try {
            Database::run("UPDATE cola_canciones SET estado = 'reproducida', finalizada_en = NOW() WHERE estado = 'reproduciendo'");
            $sig = Database::one("SELECT id FROM cola_canciones WHERE estado = 'pendiente' ORDER BY ronda, created_at, id LIMIT 1");
            if ($sig) {
                Database::run("UPDATE cola_canciones SET estado = 'reproduciendo', iniciada_en = NOW() WHERE id = ? AND estado = 'pendiente'", [$sig['id']]);
            }
            return self::ahora();
        } finally {
            Database::run("DO RELEASE_LOCK('karaoke_cola')");
        }
    }

    /** Da por terminada la que cantaba, sin poner otra. */
    public static function terminar(): bool
    {
        return Database::run("UPDATE cola_canciones SET estado = 'reproducida', finalizada_en = NOW() WHERE estado = 'reproduciendo'") > 0;
    }
}
