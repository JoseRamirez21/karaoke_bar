<?php
class Mesa
{
    public static function todas(bool $incluirInactivas = true): array
    {
        $where = $incluirInactivas ? '' : "WHERE estado <> 'inactiva'";
        return Database::all("SELECT * FROM mesas $where ORDER BY CAST(numero AS UNSIGNED), numero");
    }

    public static function buscar(int $id): ?array
    {
        return Database::one('SELECT * FROM mesas WHERE id = ?', [$id]);
    }

    public static function numeroEnUso(string $numero, int $exceptoId = 0): bool
    {
        return (int) Database::value('SELECT COUNT(*) FROM mesas WHERE numero = ? AND id <> ?', [$numero, $exceptoId]) > 0;
    }

    public static function nuevoToken(): string
    {
        return bin2hex(random_bytes(16)); // 32 caracteres
    }

    public static function crear(string $numero, int $capacidad): int
    {
        Database::run('INSERT INTO mesas (numero, capacidad, estado, qr_token) VALUES (?,?,?,?)', [$numero, $capacidad, 'libre', self::nuevoToken()]);
        return Database::lastId();
    }

    public static function actualizar(int $id, string $numero, int $capacidad): void
    {
        Database::run('UPDATE mesas SET numero = ?, capacidad = ? WHERE id = ?', [$numero, $capacidad, $id]);
    }

    public static function setEstado(int $id, string $estado): void
    {
        Database::run('UPDATE mesas SET estado = ? WHERE id = ?', [$estado, $id]);
    }

    public static function regenerarToken(int $id): void
    {
        Database::run('UPDATE mesas SET qr_token = ? WHERE id = ?', [self::nuevoToken(), $id]);
    }

    public static function tieneSesionAbierta(int $id): bool
    {
        return (int) Database::value("SELECT COUNT(*) FROM sesiones_mesa WHERE mesa_id = ? AND estado = 'abierta'", [$id]) > 0;
    }

    /** Dirección base que irá dentro de los QR (la guardada, o una sugerida según cómo abriste el sistema). */
    public static function direccionPublica(): string
    {
        $guardada = Config::get('url_publica');
        if ($guardada !== '') return $guardada;
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL;
    }
}
