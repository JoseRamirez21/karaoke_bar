<?php
class ReservasController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();   // admin y cajero
    }

    public function index(): void
    {
        $vista = in_array($_GET['ver'] ?? '', ['proximas', 'hoy', 'historial'], true) ? $_GET['ver'] : 'proximas';
        $this->view('reservas/index', ['title' => 'Reservas', 'vista' => $vista, 'reservas' => Reserva::listar($vista)]);
    }

    public function crear(): void
    {
        $this->view('reservas/form', ['title' => 'Nueva reserva', 'modo' => 'crear', 'r' => [], 'mesas' => Mesa::todas(false)]);
    }

    public function guardar(): void
    {
        $this->requirePost();
        [$d, $err] = $this->datos(0, true);
        if ($err) $this->fallar('reservas/crear', $err);

        $id = Reserva::crear($d, Auth::id());
        Bitacora::log('reserva_creada', 'reservas', $id, $d['cliente'] . ', ' . $d['fecha_hora']);
        $this->listo('reservas', 'Reserva registrada.');
    }

    public function editar(string $id = '0'): void
    {
        $id = $this->idOr404($id);
        $r  = Reserva::buscar($id) ?? $this->noExiste();
        if (!in_array($r['estado'], ['pendiente', 'confirmada'], true)) {
            flash('error', 'Esta reserva ya no se puede editar.');
            redirect('reservas?ver=historial');
        }
        $this->view('reservas/form', ['title' => 'Editar reserva', 'modo' => 'editar', 'r' => $r, 'mesas' => Mesa::todas(false)]);
    }

    public function actualizar(string $id = '0'): void
    {
        $this->requirePost();
        $id = $this->idOr404($id);
        $r  = Reserva::buscar($id) ?? $this->noExiste();
        if (!in_array($r['estado'], ['pendiente', 'confirmada'], true)) $this->fallar('reservas', 'Esta reserva ya no se puede editar.');

        [$d, $err] = $this->datos($id, false);
        if ($err) $this->fallar("reservas/editar/$id", $err);

        Reserva::actualizar($id, $d);
        Bitacora::log('reserva_editada', 'reservas', $id, $d['cliente'] . ', ' . $d['fecha_hora']);
        $this->listo('reservas', 'Cambios guardados.');
    }

    /** Confirmar, cancelar o marcar "no asistió". */
    public function estado(string $id = '0'): void
    {
        $this->requirePost();
        $id    = $this->idOr404($id);
        $r     = Reserva::buscar($id) ?? $this->noExiste();
        $nuevo = post('nuevo');
        if (!in_array($nuevo, ['confirmada', 'cancelada', 'no_asistio'], true)) $this->fallar('reservas', 'Acción no válida.');

        if (!Reserva::cambiarEstado($id, $nuevo)) $this->fallar('reservas', 'Esa reserva ya fue atendida o cancelada.');
        Bitacora::log('reserva_' . $nuevo, 'reservas', $id, $r['cliente']);
        $txt = ['confirmada' => 'Reserva confirmada.', 'cancelada' => 'Reserva cancelada.', 'no_asistio' => 'Marcada como "no asistió".'][$nuevo];
        $this->listo('reservas', $txt);
    }

    // ------------------------------------------------------------------
    /** @return array{0:array,1:?string} datos limpios y, si algo está mal, el mensaje de error */
    private function datos(int $exceptoId, bool $esNueva): array
    {
        $d = [
            'mesa_id'  => (int) post('mesa_id'),
            'cliente'  => post('cliente'),
            'telefono' => post('telefono'),
            'personas' => (int) post('personas'),
            'adelanto' => Validar::dinero(post('adelanto', '0')) ?? '',
            'notas'    => post('notas'),
            'fecha_hora' => '',
        ];
        $f = DateTime::createFromFormat('Y-m-d H:i', post('fecha') . ' ' . post('hora'));
        $okFecha = $f && $f->format('Y-m-d H:i') === post('fecha') . ' ' . post('hora');
        if ($okFecha) $d['fecha_hora'] = $f->format('Y-m-d H:i:00');

        $mesa = Mesa::buscar($d['mesa_id']);
        if (!$mesa || $mesa['estado'] === 'inactiva')                    return [$d, 'Elige una mesa.'];
        if (!Validar::largo($d['cliente'], 2, 100))                      return [$d, 'Escribe el nombre del cliente (entre 2 y 100 letras).'];
        if ($d['telefono'] !== '' && !preg_match('/^[0-9+() -]{6,20}$/', $d['telefono'])) return [$d, 'El teléfono no es válido.'];
        if ($d['personas'] < 1 || $d['personas'] > 99)                   return [$d, 'Escribe cuántas personas vienen.'];
        if ($d['personas'] > (int) $mesa['capacidad'])                   return [$d, 'La mesa ' . $mesa['numero'] . ' es para ' . (int) $mesa['capacidad'] . ' personas. Elige una más grande o reparte al grupo.'];
        if (!$okFecha)                                                   return [$d, 'Elige la fecha y la hora de la reserva.'];
        if ($esNueva && $f < new DateTime('-30 minutes'))                return [$d, 'La fecha y hora ya pasaron.'];
        if ($f > new DateTime('+1 year'))                                return [$d, 'La fecha está demasiado lejos.'];
        if ($d['adelanto'] === '')                                       return [$d, 'El adelanto debe ser un monto válido (puede ser 0).'];
        if (mb_strlen($d['notas']) > 255)                                return [$d, 'Las notas son muy largas (máximo 255 caracteres).'];

        if ($c = Reserva::conflicto($d['mesa_id'], $d['fecha_hora'], $exceptoId)) {
            return [$d, 'La mesa ' . $mesa['numero'] . ' ya tiene una reserva cerca de esa hora (' . $c['cliente'] . ', ' . hora12($c['fecha_hora']) . '). Deja al menos 2 horas entre reservas.'];
        }
        return [$d, null];
    }

    private function noExiste(): never
    {
        (new ErrorController())->show404();
        exit;
    }
}
