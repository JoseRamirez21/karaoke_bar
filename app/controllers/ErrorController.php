<?php
class ErrorController extends Controller
{
    private function mostrar(int $code, string $title, string $message): void
    {
        http_response_code($code);
        $layout = Auth::check() ? 'main' : 'auth';
        $this->view('errors/error', compact('code', 'title', 'message'), $layout);
    }

    public function show404(): void
    {
        $this->mostrar(404, 'No encontramos esa página', 'La dirección no existe o todavía no está lista.');
    }

    public function show403(): void
    {
        $this->mostrar(403, 'No tienes permiso para entrar aquí', 'Esta sección es solo para el administrador. Si la necesitas, pídele acceso.');
    }

    public function show500(?string $detail = null): void
    {
        $this->mostrar(500, 'Algo salió mal', $detail ?: 'Tuvimos un problema al procesar tu solicitud. Intenta de nuevo en unos segundos.');
    }
}
