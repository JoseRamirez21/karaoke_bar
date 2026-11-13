<?php
class ProductosController extends Controller
{
    public function __construct()
    {
        $this->requireAuth(['admin']);
    }

    public function index(): void
    {
        $this->view('productos/index', [
            'title'      => 'Productos',
            'productos'  => Producto::todos(),
            'categorias' => Categoria::activas(),
        ]);
    }

    public function crear(): void
    {
        $this->view('productos/form', [
            'title' => 'Nuevo producto', 'modo' => 'crear', 'p' => [],
            'categorias' => Categoria::activas(), 'movimientos' => [],
        ]);
    }

    public function guardar(): void
    {
        $this->requirePost();
        $d     = $this->datos();
        $stock = Validar::entero(post('stock', '0'));

        $err = $this->validar($d, 0);
        if (!$err && $stock === null) $err = 'El stock inicial debe ser un número entero de 0 o más.';
        if ($err) $this->fallar('productos/crear', $err);

        $id = Producto::crear($d, $stock, Auth::id());
        $this->listo('productos', 'Producto creado.');
    }

    public function editar(string $id = '0'): void
    {
        $id = $this->idOr404($id);
        $p  = Producto::buscar($id) ?? $this->noExiste();

        $categorias = Categoria::activas();
        if (!in_array($p['categoria_id'], array_column($categorias, 'id'))) {   // categoría desactivada: la mostramos igual
            $categorias[] = ['id' => $p['categoria_id'], 'nombre' => $p['categoria'] . ' (desactivada)'];
        }
        $this->view('productos/form', [
            'title' => 'Editar producto', 'modo' => 'editar', 'p' => $p,
            'categorias' => $categorias, 'movimientos' => Producto::movimientos($id),
        ]);
    }

    public function actualizar(string $id = '0'): void
    {
        $this->requirePost();
        $id = $this->idOr404($id);
        $p  = Producto::buscar($id) ?? $this->noExiste();

        $d   = $this->datos();
        $err = $this->validar($d, $id, (int) $p['categoria_id']);
        if ($err) $this->fallar("productos/editar/$id", $err);

        Producto::actualizar($id, $d);
        $this->listo('productos', 'Cambios guardados.');
    }

    public function estado(string $id = '0'): void
    {
        $this->requirePost();
        $id = $this->idOr404($id);
        $p  = Producto::buscar($id) ?? $this->noExiste();

        $nuevo = (int) $p['estado'] === 1 ? 0 : 1;
        Producto::setEstado($id, $nuevo);
        $this->listo('productos', $nuevo ? 'Producto disponible para la venta.' : 'Producto desactivado. Ya no aparecerá para vender.');
    }

    public function stock(string $id = '0'): void
    {
        $this->requirePost();
        $id = $this->idOr404($id);
        $p  = Producto::buscar($id) ?? $this->noExiste();

        $tipo     = post('tipo');
        $cantidad = Validar::entero(post('cantidad'));
        $motivo   = post('motivo');

        if (!in_array($tipo, ['entrada', 'salida', 'ajuste'], true)) $this->fallar("productos/editar/$id", 'Elige qué quieres hacer con el stock.');
        if ($cantidad === null || ($tipo !== 'ajuste' && $cantidad < 1)) $this->fallar("productos/editar/$id", 'La cantidad debe ser un número entero' . ($tipo === 'ajuste' ? ' (0 o más).' : ' mayor a 0.'));
        if ($tipo !== 'entrada' && !Validar::largo($motivo, 3, 150)) $this->fallar("productos/editar/$id", 'Escribe el motivo (por ejemplo: rotura, conteo del inventario).');

        try {
            $nuevo = Producto::ajustarStock($id, $tipo, $cantidad, $motivo, Auth::id());
        } catch (AppException $e) {
            $this->fallar("productos/editar/$id", $e->getMessage());
        }
        $this->listo("productos/editar/$id", "Stock actualizado: ahora hay $nuevo.");
    }

    private function datos(): array
    {
        $precio = Validar::dinero(post('precio'));
        $min    = Validar::entero(post('stock_minimo', '0'));
        return [
            'categoria_id' => (int) post('categoria_id'),
            'nombre'       => post('nombre'),
            'precio'       => $precio ?? '',
            'stock_minimo' => $min ?? -1,
            'estado'       => isset($_POST['estado']) ? 1 : 0,
        ];
    }

    private function validar(array $d, int $exceptoId, int $categoriaActualId = 0): ?string
    {
        if (!Validar::largo($d['nombre'], 2, 120)) return 'El nombre del producto debe tener entre 2 y 120 caracteres.';
        $cat = Categoria::buscar($d['categoria_id']);
        if (!$cat || ((int) $cat['estado'] !== 1 && $d['categoria_id'] !== $categoriaActualId)) return 'Elige una categoría válida.';
        if ($d['precio'] === '' || (float) $d['precio'] <= 0) return 'El precio debe ser mayor a 0 (por ejemplo 12.50).';
        if ($d['stock_minimo'] < 0) return 'El stock mínimo debe ser un número entero de 0 o más.';
        if (Producto::nombreEnUso($d['nombre'], $exceptoId)) return 'Ya existe un producto con ese nombre.';
        return null;
    }

    private function noExiste(): never
    {
        (new ErrorController())->show404();
        exit;
    }
}
