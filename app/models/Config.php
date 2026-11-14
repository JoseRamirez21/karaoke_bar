<?php

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

    public static function set(string $clave, string $valor): void
    {
        Database::run('INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)', [$clave, $valor]);
        self::$cache = null;
    }
}
