<?php
class Validar
{
    public static function password(string $p): ?string
    {
        if (mb_strlen($p) < 8)                                  return 'La contraseña debe tener al menos 8 caracteres.';
        if (!preg_match('/[A-Za-z]/', $p) || !preg_match('/\d/', $p)) return 'La contraseña debe tener al menos una letra y un número.';
        return null;
    }

    public static function largo(string $v, int $min, int $max): bool
    {
        $n = mb_strlen($v);
        return $n >= $min && $n <= $max;
    }

    /** Entero >= 0 de hasta 6 cifras, o null si no es válido. */
    public static function entero(string $v): ?int
    {
        return preg_match('/^\d{1,6}$/', $v) ? (int) $v : null;
    }

    /** Dinero con hasta 2 decimales (acepta coma). Devuelve "12.50" o null. */
    public static function dinero(string $v): ?string
    {
        $v = str_replace(',', '.', trim($v));
        if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $v)) return null;
        return number_format((float) $v, 2, '.', '');
    }
}
