<?php
class MesasController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();   // el cajero también ve el salón; lo de administrar es solo del admin
    }

    public function index(): void
    {
        $esAdmin = Auth::is('admin');
        $this->view('mesas/index', [
            'title'   => 'Salón y mesas',
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
        Bitacora::log('mesa_creada', 'mesas', $id, "Mesa $numero para $cap personas");
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
        Bitacora::log('mesa_editada', 'mesas', $id, "Mesa {$m['numero']} → $numero, $cap personas");
        $this->listo('mesas', 'Cambios guardados.');
    }

    /** Desactiva una mesa libre (o la vuelve a activar). */
    public function estado(string $id = '0'): void
    {
        $this->soloAdmin();
        $this->requirePost();
        $id = $this->idOr404($id);
        $m  = Mesa::buscar($id) ?? $this->noExiste();

        if ($m['estado'] === 'inactiva') {
            Mesa::setEstado($id, 'libre');
            Bitacora::log('mesa_activada', 'mesas', $id, 'Mesa ' . $m['numero']);
            $this->listo('mesas', 'Mesa activada.');
        }
        if ($m['estado'] !== 'libre' || Mesa::tieneSesionAbierta($id)) {
            flash('error', 'Solo puedes desactivar una mesa que esté libre. Esta tiene una cuenta abierta o una reserva.');
            redirect('mesas');
        }
        Mesa::setEstado($id, 'inactiva');
        Bitacora::log('mesa_desactivada', 'mesas', $id, 'Mesa ' . $m['numero']);
        $this->listo('mesas', 'Mesa desactivada.');
    }

    /** Genera un QR nuevo (el impreso deja de funcionar). Útil si alguien lo fotografió o se perdió. */
    public function token(string $id = '0'): void
    {
        $this->soloAdmin();
        $this->requirePost();
        $id = $this->idOr404($id);
        $m  = Mesa::buscar($id) ?? $this->noExiste();

        Mesa::regenerarToken($id);
        Bitacora::log('qr_regenerado', 'mesas', $id, 'Mesa ' . $m['numero']);
        $this->listo("mesas/qr/$id", 'QR nuevo generado. Imprímelo y reemplaza el anterior, que ya no funciona.');
    }

    /** Página para imprimir los QR (todos, o solo el de una mesa). */
    public function qr(string $id = '0'): void
    {
        $this->soloAdmin();
        if ($id !== '0') {
            $id = $this->idOr404($id);
            $m  = Mesa::buscar($id) ?? $this->noExiste();
            $mesas = [$m];
        } else {
            $mesas = Mesa::todas(false);
        }
        $this->view('mesas/qr', [
            'title'     => 'Códigos QR',
            'mesas'     => $mesas,
            'unaSola'   => $id !== '0',
            'direccion' => Mesa::direccionPublica(),
        ]);
    }

    public function direccion(): void
    {
        $this->soloAdmin();
        $this->requirePost();
        $dir = rtrim(post('direccion'), '/');
        $p   = parse_url($dir);
        if (!filter_var($dir, FILTER_VALIDATE_URL) || !in_array($p['scheme'] ?? '', ['http', 'https'], true) || mb_strlen($dir) > 200) {
            $this->fallar('mesas/qr', 'Escribe una dirección válida, por ejemplo http://192.168.1.50/karaoke_bar');
        }
        Config::set('url_publica', $dir);
        Bitacora::log('url_publica_cambiada', 'configuracion', null, $dir);
        $this->listo('mesas/qr', 'Dirección guardada. Los QR ya la usan.');
    }

    // ------------------------------------------------------------------
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
