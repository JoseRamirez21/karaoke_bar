<?php
class CategoriasController extends Controller
{
    public function __construct()
    {
        $this->requireAuth(['admin']);
    }

    public function index(): void
    {
        $this->view('categorias/index', ['title' => 'Categorías', 'categorias' => Categoria::todas()]);
    }

    public function guardar(): void
    {
        $this->requirePost();
        $nombre = post('nombre');
        $err = $this->validar($nombre, 0);
        if ($err) $this->fallar('categorias', $err);

        $id = Categoria::crear($nombre);
        Bitacora::log('categoria_creada', 'categorias', $id, $nombre);
        $this->listo('categorias', 'Categoría creada.');
    }

    public function actualizar(string $id = '0'): void
    {
        $this->requirePost();
        $id = $this->idOr404($id);
        $c  = Categoria::buscar($id) ?? $this->noExiste();

        $nombre = post('nombre');
        $err = $this->validar($nombre, $id);
        if ($err) $this->fallar('categorias', $err);

        Categoria::renombrar($id, $nombre);
        Bitacora::log('categoria_editada', 'categorias', $id, $c['nombre'] . ' → ' . $nombre);
        $this->listo('categorias', 'Categoría actualizada.');
    }

    public function estado(string $id = '0'): void
    {
        $this->requirePost();
        $id = $this->idOr404($id);
        $c  = Categoria::buscar($id) ?? $this->noExiste();

        if ((int) $c['estado'] === 1) {
            $activos = (int) Database::value('SELECT COUNT(*) FROM productos WHERE categoria_id = ? AND estado = 1', [$id]);
            if ($activos > 0) {
                flash('error', "No se puede desactivar: tiene $activos producto" . ($activos > 1 ? 's' : '') . ' activo' . ($activos > 1 ? 's' : '') . '. Primero desactívalos o cámbialos de categoría.');
                redirect('categorias');
            }
        }
        $nuevo = (int) $c['estado'] === 1 ? 0 : 1;
        Categoria::setEstado($id, $nuevo);
        Bitacora::log($nuevo ? 'categoria_activada' : 'categoria_desactivada', 'categorias', $id, $c['nombre']);
        $this->listo('categorias', $nuevo ? 'Categoría activada.' : 'Categoría desactivada.');
    }

    private function validar(string $nombre, int $exceptoId): ?string
    {
        if (!Validar::largo($nombre, 2, 80))                return 'El nombre de la categoría debe tener entre 2 y 80 caracteres.';
        if (Categoria::nombreEnUso($nombre, $exceptoId))    return 'Ya existe una categoría con ese nombre.';
        return null;
    }

    private function noExiste(): never
    {
        (new ErrorController())->show404();
        exit;
    }
}
