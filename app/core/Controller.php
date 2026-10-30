<?php
abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'main'): void
    {
        $GLOBALS['_old'] = $_SESSION['_old'] ?? [];
        unset($_SESSION['_old']);
        $flash = pull_flash();

        extract($data, EXTR_SKIP);
        ob_start();
        require APP_PATH . "/views/$view.php";
        $content = ob_get_clean();
        require APP_PATH . "/views/layouts/$layout.php";
    }

    /** Exige sesión (y opcionalmente uno de los roles indicados). */
    protected function requireAuth(array $roles = []): void
    {
        if (!Auth::check()) redirect('auth/login');
        Auth::refresh();
        if (!Auth::check()) redirect('auth/login');
        if ($roles && !in_array(Auth::role(), $roles, true)) {
            (new ErrorController())->show403();
            exit;
        }
    }

    /** Solo acepta POST con token CSRF válido. */
    protected function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit('Método no permitido');
        }
        Csrf::verifyOrFail();
    }

    protected function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
