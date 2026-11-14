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

    
    protected function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit('Método no permitido');
        }
        Csrf::verifyOrFail();
    }

    
    protected function idOr404(string $id): int
    {
        $n = (int) $id;
        if ($n <= 0 || (string) $n !== $id) {
            (new ErrorController())->show404();
            exit;
        }
        return $n;
    }

    
    protected function fallar(string $ruta, string $mensaje): never
    {
        $old = $_POST;
        unset($old['_csrf'], $old['password'], $old['actual'], $old['nueva'], $old['repetir']);
        with_old($old);
        flash('error', $mensaje);
        redirect($ruta);
    }

    protected function listo(string $ruta, string $mensaje): never
    {
        flash('success', $mensaje);
        redirect($ruta);
    }
}
