<?php
class MesasController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    public function index(): void
    {
        $esAdmin = Auth::is('admin');
        $this->view('mesas/index', [
            'title'   => 'Mesas',
            'esAdmin' => $esAdmin,
            'mesas'   => Mesa::todas($esAdmin),
        ]);
    }

    public function crear(): void
    {
        $this->soloAdmin();
        $this->view('mesas/form', ['title' => 'Nueva mesa', 'modo' => 'crear', 'm' => []]);
    }

    public function guardar(): void
    {
        $this->soloAdmin();
        $this->requirePost();
        $numero = post('numero');
        $cap    = (int) post('capacidad');
        $err = $this->validar($numero, $cap, 0);
        if ($err) $this->fallar('mesas/crear', $err);

        $id = Mesa::crear($numero, $cap);
        $this->listo('mesas', "Mesa $numero creada.");
    }

    public function editar(string $id = '0'): void
    {
        $this->soloAdmin();
        $id = $this->idOr404($id);
        $m  = Mesa::buscar($id) ?? $this->noExiste();
        $this->view('mesas/form', ['title' => 'Editar mesa', 'modo' => 'editar', 'm' => $m]);
    }

    public function actualizar(string $id = '0'): void
    {
        $this->soloAdmin();
        $this->requirePost();
        $id = $this->idOr404($id);
        $m  = Mesa::buscar($id) ?? $this->noExiste();

        $numero = post('numero');
        $cap    = (int) post('capacidad');
        $err = $this->validar($numero, $cap, $id);
        if ($err) $this->fallar("mesas/editar/$id", $err);

        Mesa::actualizar($id, $numero, $cap);
        $this->listo('mesas', 'Cambios guardados.');
    }

    
    public function estado(string $id = '0'): void
    {
        $this->soloAdmin();
        $this->requirePost();
        $id = $this->idOr404($id);
        $m  = Mesa::buscar($id) ?? $this->noExiste();

        if ($m['estado'] === 'inactiva') {
            Mesa::setEstado($id, 'libre');
            $this->listo('mesas', 'Mesa activada.');
        }
        if ($m['estado'] !== 'libre' || Mesa::tieneSesionAbierta($id)) {
            flash('error', 'Solo puedes desactivar una mesa que esté libre. Esta tiene una cuenta abierta.');
            redirect('mesas');
        }
        Mesa::setEstado($id, 'inactiva');
        $this->listo('mesas', 'Mesa desactivada.');
    }

    
    public function abrir(string $id = '0'): void
    {
        $id = $this->idOr404($id);
        $m  = Mesa::buscar($id) ?? $this->noExiste();

        if ($m['estado'] === 'inactiva') {
            flash('error', 'Mesa desactivada.');
            redirect('mesas');
        }
        if ($s = SesionMesa::abierta($id)) redirect('mesas/cuenta/' . (int) $s['id']);

        $this->view('mesas/abrir', ['title' => 'Abrir mesa ' . $m['numero'], 'm' => $m]);
    }

    public function iniciar(string $id = '0'): void
    {
        $this->requirePost();
        $id = $this->idOr404($id);
        $m  = Mesa::buscar($id) ?? $this->noExiste();

        $cliente = post('cliente');
        if (mb_strlen($cliente) > 100) $this->fallar("mesas/abrir/$id", 'La referencia es muy larga (máximo 100 caracteres).');

        try {
            $sid = SesionMesa::abrir($id, Auth::id(), $cliente);
        } catch (AppException $e) {
            $this->fallar("mesas/abrir/$id", $e->getMessage());
        }
        $this->listo('mesas/cuenta/' . $sid, 'Mesa ' . $m['numero'] . ' abierta.');
    }

    
    public function cuenta(string $id = '0'): void
    {
        $id = $this->idOr404($id);
        $s  = SesionMesa::buscar($id) ?? $this->noExiste();
        $this->view('mesas/cuenta', [
            'title'     => 'Mesa ' . $s['mesa'],
            's'         => $s,
            'ventas'    => SesionMesa::ventas($id),
            'resumen'   => SesionMesa::resumen($id),
        ]);
    }

    public function cerrar(string $id = '0'): void
    {
        $this->requirePost();
        $id = $this->idOr404($id);
        $s  = SesionMesa::buscar($id) ?? $this->noExiste();

        try {
            $r = SesionMesa::cerrar($id, Auth::id());
        } catch (AppException $e) {
            $this->fallar("mesas/cuenta/$id", $e->getMessage());
        }

        $msg = 'Mesa ' . $s['mesa'] . ' cerrada. Consumo total: ' . money($r['total']) . '.';
        $this->listo('mesas/cuenta/' . $id, $msg);
    }

    private function soloAdmin(): void
    {
        if (!Auth::is('admin')) {
            (new ErrorController())->show403();
            exit;
        }
    }

    private function validar(string $numero, int $cap, int $exceptoId): ?string
    {
        if (!Validar::largo($numero, 1, 20))     return 'El número o nombre de la mesa debe tener entre 1 y 20 caracteres (por ejemplo 7 o VIP1).';
        if ($cap < 1 || $cap > 30)               return 'La capacidad debe estar entre 1 y 30 personas.';
        if (Mesa::numeroEnUso($numero, $exceptoId)) return 'Ya existe una mesa con ese número.';
        return null;
    }

    private function noExiste(): never
    {
        (new ErrorController())->show404();
        exit;
    }
}
