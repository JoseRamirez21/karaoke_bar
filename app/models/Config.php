<?php
/** Lee la tabla `configuracion` (clave/valor) con caché por petición. */
class Config
{
    private static ?array $cache = null;

    public static function get(string $clave, string $default = ''): string
    {
        if (self::$cache === null) {
            try {
                self::$cache = array_column(Database::all('SELECT clave, valor FROM configuracion'), 'valor', 'clave');
            } catch (Throwable $e) {
                self::$cache = [];
            }
        }
        return self::$cache[$clave] ?? $default;
    }
}
