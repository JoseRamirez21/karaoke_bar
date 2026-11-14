<?php
class VentasController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();   
    }

    
    public function index(): void
    {
        $turno = Caja::abierto(Auth::id());
        $mesas = Venta::mesasAbiertas();

        
        $pre = (int) ($_GET['sesion'] ?? 0);
        if (!in_array($pre, array_map('intval', array_column($mesas, 'sesion_id')), true)) $pre = 0;

        
        $tokens   = $_SESSION['venta_tokens'] ?? [];
        $token    = bin2hex(random_bytes(16));
        $tokens[] = $token;
        $_SESSION['venta_tokens'] = array_slice($tokens, -10);

        $productos  = $turno ? Venta::productosVendibles() : [];
        $categorias = [];
        foreach ($productos as $p) $categorias[$p['categoria_id']] = $p['categoria'];

        $this->view('ventas/index', [
            'title'      => 'Vender',
            'turno'      => $turno,
            'productos'  => $productos,
            'combos'     => $turno ? Combo::vendibles() : [],
            'categorias' => $categorias,
            'mesas'      => $mesas,
            'metodos'    => Venta::metodos(),
            'sesionPre'  => $pre,
            'token'      => $token,
        ]);
    }

    public function guardar(): void
    {
        $this->requirePost();

        $turno = Caja::abierto(Auth::id());
        if (!$turno) $this->fallar('ventas', 'Primero abre tu turno de caja para poder vender.');

        $sesionId = max(0, (int) post('sesion_id'));            
        $ruta     = 'ventas' . ($sesionId > 0 ? '?sesion=' . $sesionId : '');

        
        $tokens = $_SESSION['venta_tokens'] ?? [];
        $k      = array_search((string) ($_POST['token'] ?? ''), $tokens, true);
        if ($k === false) $this->fallar($ruta, 'Este pedido ya se procesó o la pantalla venció. Revisa si ya se cobró antes de repetirlo.');
        unset($tokens[$k]);
        $_SESSION['venta_tokens'] = array_values($tokens);

        
        $items  = $this->cantidades($_POST['items'] ?? []);
        $combos = $this->cantidades($_POST['combos'] ?? []);
        if (!$items && !$combos)              $this->fallar($ruta, 'Agrega al menos un producto al pedido.');
        if (count($items) + count($combos) > 60) $this->fallar($ruta, 'El pedido tiene demasiados productos distintos. Sepáralo en dos.');

        $recibido = null;
        if (post('recibido') !== '') {
            $recibido = Validar::dinero(post('recibido'));
            if ($recibido === null) $this->fallar($ruta, 'El efectivo recibido no es válido. Escríbelo con números, por ejemplo 100.');
        }

        try {
            $v = Venta::crear(Auth::id(), (int) $turno['id'], $sesionId > 0 ? $sesionId : null, (int) post('metodo_id'), $items, $combos, $recibido);
        } catch (AppException $e) {
            $this->fallar($ruta, $e->getMessage());
        }


        $msg = 'Venta ' . $v['codigo'] . ' cobrada: ' . money($v['total']) . '.';
        if ($v['vuelto'] !== null && $v['vuelto'] > 0.004) $msg .= ' Vuelto: ' . money($v['vuelto']) . '.';
        $this->listo('ventas/ticket/' . $v['id'], $msg);
    }

    
    public function ticket(string $id = '0'): void
    {
        $id = $this->idOr404($id);
        $v  = Venta::buscar($id);
        if (!$v) { (new ErrorController())->show404(); exit; }

        $turno = Database::one('SELECT id, estado FROM turnos_caja WHERE id = ?', [$v['turno_id']]);
        $this->view('ventas/ticket', [
            'title'         => 'Ticket ' . $v['codigo'],
            'v'             => $v,
            'items'         => Venta::detalle($id),
            'razonNoAnular' => Venta::razonNoAnular($v, $turno, Auth::id(), Auth::is('admin')),
        ]);
    }

    
    public function entregar(string $id = '0'): void
    {
        $this->requirePost();
        $id = $this->idOr404($id);
        $v  = Venta::buscar($id);
        if (!$v) { (new ErrorController())->show404(); exit; }

        
        $volver = post('volver');
        if (!preg_match('#^(ventas/ticket|mesas/cuenta)/\d+$#', $volver)) $volver = 'ventas/ticket/' . $id;

        try {
            Venta::entregar($id);
        } catch (AppException $e) {
            $this->fallar($volver, $e->getMessage());
        }
        $this->listo($volver, 'Pedido ' . $v['codigo'] . ' entregado.');
    }

    
    public function anular(string $id = '0'): void
    {
        $this->requirePost();
        $id = $this->idOr404($id);
        $v  = Venta::buscar($id);
        if (!$v) { (new ErrorController())->show404(); exit; }

        $ruta   = 'ventas/ticket/' . $id;
        $motivo = post('motivo');
        if (!Validar::largo($motivo, 3, 200)) $this->fallar($ruta, 'Escribe el motivo de la anulación (mínimo 3 letras).');

        try {
            Venta::anular($id, Auth::id(), Auth::is('admin'), $motivo);
        } catch (AppException $e) {
            $this->fallar($ruta, $e->getMessage());
        }
        $this->listo($ruta, 'Pedido ' . $v['codigo'] . ' anulado. Se devolvió el stock.');
    }

    
    private function cantidades(mixed $raw): array
    {
        $r = [];
        if (is_array($raw)) {
            foreach ($raw as $id => $q) {
                $q = Validar::entero((string) $q);
                if ((int) $id > 0 && $q !== null && $q > 0) $r[(int) $id] = $q;
            }
        }
        return $r;
    }
}
