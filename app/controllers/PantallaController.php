<?php
/** La pantalla del karaoke: se abre en la PC conectada a la TV, con la sesión de un trabajador. */
class PantallaController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $this->view('pantalla/index', ['title' => 'Pantalla de karaoke', 'csrf' => Csrf::token()], 'pantalla');
    }

    /** Lo que la pantalla necesita saber cada pocos segundos. */
    public function estado(): void
    {
        $ahora = Cola::ahora();
        $pend  = Cola::pendientes(500);
        $this->json([
            'ahora' => $ahora ? [
                'id' => (int) $ahora['id'], 'video_id' => $ahora['video_id'], 'titulo' => $ahora['titulo'], 'mesa' => $ahora['mesa'],
            ] : null,
            'pendientes' => count($pend),
            'siguientes' => array_map(fn($c) => ['titulo' => $c['titulo'], 'mesa' => $c['mesa']], array_slice($pend, 0, 6)),
        ]);
    }

    /** Arranca la siguiente canción (si no hay una sonando). */
    public function siguiente(): void
    {
        $this->csrfJson();
        $a = Cola::promover();
        $this->json(['actual' => $a ? ['id' => (int) $a['id'], 'video_id' => $a['video_id']] : null]);
    }

    /** La canción terminó (resultado "ok") o no se pudo reproducir (resultado "error"). */
    public function terminar(): void
    {
        $this->csrfJson();
        $ok = Cola::terminar((int) post('id'), post('resultado') === 'error');
        $this->json(['ok' => $ok]);
    }

    private function csrfJson(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') $this->json(['error' => 'Método no permitido'], 405);
        if (!Csrf::valid($_POST['_csrf'] ?? null)) $this->json(['error' => 'Sesión vencida: recarga la pantalla'], 419);
    }
}