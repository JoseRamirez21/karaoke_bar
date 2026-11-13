<?php
class CombosController extends Controller
{
    public function __construct()
    {
        $this->requireAuth(['admin']);
    }

    public function index(): void
    {
        $this->view('combos/index', ['title' => 'Combos', 'combos' => Combo::todos(), 'resumenes' => Combo::resumenes()]);
    }

    public function crear(): void
    {
        $this->view('combos/form', ['title' => 'Nuevo combo', 'modo' => 'crear', 'c' => [], 'actuales' => [], 'productos' => $this->productos()]);
    }

    public function guardar(): void
    {
        $this->requirePost();
        [$d, $comp, $err] = $this->datos(0);
        if ($err) $this->fallar('combos/crear', $err);

        $id = Combo::guardar(null, $d, $comp);
        $this->listo('combos', 'Combo creado.');
    }

    public function editar(string $id = '0'): void
    {
        $id = $this->idOr404($id);
        $c  = Combo::buscar($id) ?? $this->noExiste();
        $this->view('combos/form', ['title' => 'Editar combo', 'modo' => 'editar', 'c' => $c, 'actuales' => Combo::componentes($id), 'productos' => $this->productos()]);
    }

    public function actualizar(string $id = '0'): void
    {
        $this->requirePost();
        $id = $this->idOr404($id);
        $c  = Combo::buscar($id) ?? $this->noExiste();
        [$d, $comp, $err] = $this->datos($id);
        if ($err) $this->fallar("combos/editar/$id", $err);

        Combo::guardar($id, $d, $comp);
        $this->listo('combos', 'Cambios guardados.');
    }

    public function estado(string $id = '0'): void
    {
        $this->requirePost();
        $id = $this->idOr404($id);
        $c  = Combo::buscar($id) ?? $this->noExiste();
        $nuevo = (int) $c['estado'] === 1 ? 0 : 1;
        Combo::setEstado($id, $nuevo);
        $this->listo('combos', $nuevo ? 'Combo activado: ya se puede vender.' : 'Combo desactivado: ya no aparece para vender.');
    }

    private function productos(): array
    {
        return Database::all(
            'SELECT p.id, p.nombre, p.precio, p.stock, c.nombre AS categoria
               FROM productos p JOIN categorias c ON c.id = p.categoria_id
              WHERE p.estado = 1 ORDER BY c.nombre, p.nombre'
        );
    }

    /** @return array{0:array,1:array<int,int>,2:?string} */
    private function datos(int $exceptoId): array
    {
        $d = [
            'nombre'      => post('nombre'),
            'descripcion' => post('descripcion'),
            'precio'      => Validar::dinero(post('precio')) ?? '',
            'desde'       => post('desde'),
            'hasta'       => post('hasta'),
            'estado'      => isset($_POST['estado']) ? 1 : 0,
        ];
        $comp = [];
        $raw  = $_POST['comp'] ?? [];
        if (is_array($raw)) {
            foreach ($raw as $pid => $q) {
                $q = Validar::entero((string) $q);
                if ((int) $pid > 0 && $q !== null && $q > 0) $comp[(int) $pid] = min($q, 99);
            }
        }
        $fecha = fn(string $f) => $f === '' || (($x = DateTime::createFromFormat('Y-m-d', $f)) && $x->format('Y-m-d') === $f);

        if (!Validar::largo($d['nombre'], 2, 120))                     return [$d, $comp, 'El nombre del combo debe tener entre 2 y 120 caracteres.'];
        if (mb_strlen($d['descripcion']) > 255)                         return [$d, $comp, 'La descripción es muy larga (máximo 255 caracteres).'];
        if (Combo::nombreEnUso($d['nombre'], $exceptoId))               return [$d, $comp, 'Ya existe un combo con ese nombre.'];
        if ($d['precio'] === '' || (float) $d['precio'] <= 0)           return [$d, $comp, 'El precio del combo debe ser mayor a 0 (por ejemplo 55.00).'];
        if (!$fecha($d['desde']) || !$fecha($d['hasta']))               return [$d, $comp, 'Las fechas de vigencia no son válidas.'];
        if ($d['desde'] !== '' && $d['hasta'] !== '' && $d['desde'] > $d['hasta']) return [$d, $comp, 'La fecha "desde" no puede ser posterior a "hasta".'];
        if (!$comp)                                                     return [$d, $comp, 'Elige al menos un producto para el combo (pon una cantidad mayor a 0).'];
        if (count($comp) > 30)                                          return [$d, $comp, 'Un combo puede tener hasta 30 productos distintos.'];

        // Todos los productos deben existir y estar activos
        $marcas = implode(',', array_fill(0, count($comp), '?'));
        $validos = (int) Database::value("SELECT COUNT(*) FROM productos WHERE estado = 1 AND id IN ($marcas)", array_keys($comp));
        if ($validos !== count($comp))                                  return [$d, $comp, 'Alguno de los productos elegidos ya no está disponible. Recarga la página.'];
        return [$d, $comp, null];
    }

    private function noExiste(): never
    {
        (new ErrorController())->show404();
        exit;
    }
}
