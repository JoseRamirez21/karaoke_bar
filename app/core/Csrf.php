<?php
class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function valid(?string $token): bool
    {
        return !empty($_SESSION['_csrf']) && is_string($token) && hash_equals($_SESSION['_csrf'], $token);
    }

    public static function verifyOrFail(): void
    {
        if (!self::valid($_POST['_csrf'] ?? null)) {
            flash('error', 'La página expiró. Vuelve a intentarlo.');
            redirect(Auth::check() ? 'dashboard' : 'auth/login');
        }
    }
}
