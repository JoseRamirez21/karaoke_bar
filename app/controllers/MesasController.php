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

    // ------------------------------------------------------------------
    // Operación del salón (admin y cajero)
    // ------------------------------------------------------------------

    /** Formulario para abrir una mesa libre (o reservada). */
    public function abrir(string $id = '0'): void
    {
        $id = $this->idOr404($id);
        $m  = Mesa::buscar($id) ?? $this->noExiste();

        if ($m['estado'] === 'inactiva') {
            flash('error', 'Esta mesa está desactivada.');
            redirect('mesas');
        }
        if ($s = SesionMesa::abierta($id)) {          // ya está abierta: vamos directo a su cuenta
            redirect('mesas/cuenta/' . (int) $s['id']);
        }
        // Si la mesa tiene una reserva cercana (o venimos desde la lista de reservas), se propone usarla
        $reserva = null;
        $rid = (int) ($_GET['reserva'] ?? 0);
        if ($rid > 0) {
            $r = Reserva::buscar($rid);
            if ($r && (int) $r['mesa_id'] === $id && in_array($r['estado'], ['pendiente', 'confirmada'], true)) $reserva = $r;
        }
        $reserva ??= Reserva::paraMesaAhora($id);
        $this->view('mesas/abrir', ['title' => 'Abrir mesa ' . $m['numero'], 'm' => $m, 'reserva' => $reserva]);
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
        $rid = (int) post('reserva_id');
        if ($rid > 0 && Reserva::cumplir($rid, $id)) Bitacora::log('reserva_cumplida', 'reservas', $rid, 'Mesa ' . $m['numero']);
        Bitacora::log('mesa_abierta', 'sesiones_mesa', $sid, 'Mesa ' . $m['numero'] . ($cliente !== '' ? ', ' . $cliente : ''));
        $this->listo('mesas/cuenta/' . $sid, 'Mesa ' . $m['numero'] . ' abierta.');
    }

    /** La cuenta de una mesa (el id de la URL es el de la sesión). */
    public function cuenta(string $id = '0'): void
    {
        $id = $this->idOr404($id);
        $s  = SesionMesa::buscar($id) ?? $this->noExiste();
        $this->view('mesas/cuenta', [
            'title'     => 'Mesa ' . $s['mesa'],
            's'         => $s,
            'ventas'    => SesionMesa::ventas($id),
            'resumen'   => SesionMesa::resumen($id),
            'canciones' => SesionMesa::canciones($id),
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
        Bitacora::log('mesa_cerrada', 'sesiones_mesa', $id, 'Mesa ' . $s['mesa'] . ', consumo ' . money($r['total']));

        $msg = 'Mesa ' . $s['mesa'] . ' cerrada. Consumo total: ' . money($r['total']) . '.';
        if ($r['quitadas'] > 0) $msg .= ' Se quitaron ' . $r['quitadas'] . ($r['quitadas'] > 1 ? ' canciones pendientes.' : ' canción pendiente.');
        $this->listo('mesas/cuenta/' . $id, $msg);
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
