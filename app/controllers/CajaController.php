<?php
class CajaController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();   // admin y cajero
    }

    public function index(): void
    {
        $turno = Caja::abierto(Auth::id());
        $this->view('caja/index', [
            'title'     => 'Mi caja',
            'turno'     => $turno,
            'resumen'   => $turno ? Caja::resumen((int) $turno['id']) : null,
            // el admin ve los turnos de todos; el cajero, solo los suyos
            'historial' => Caja::historial(Auth::is('admin') ? null : Auth::id()),
        ]);
    }

    public function abrir(): void
    {
        $this->requirePost();
        $monto = Validar::dinero(post('monto_inicial', '0'));
        if ($monto === null) $this->fallar('caja', 'Escribe el efectivo inicial con números, por ejemplo 50 o 50.00 (puede ser 0).');

        try {
            $id = Caja::abrir(Auth::id(), $monto);
        } catch (AppException $e) {
            $this->fallar('caja', $e->getMessage());
        }
        Bitacora::log('turno_abierto', 'turnos_caja', $id, 'Fondo inicial: ' . money($monto));
        $this->listo('caja', 'Turno abierto. ¡Buena jornada!');
    }

    public function cerrar(): void
    {
        $this->requirePost();
        $turno = Caja::abierto(Auth::id());
        if (!$turno) $this->fallar('caja', 'No tienes un turno abierto.');

        $contado = Validar::dinero(post('efectivo_contado'));
        if ($contado === null) $this->fallar('caja', 'Escribe el efectivo que contaste, con números (puede ser 0).');

        try {
            $r = Caja::cerrar((int) $turno['id'], $contado, post('observacion'));
        } catch (AppException $e) {
            $this->fallar('caja', $e->getMessage());
        }

        $dif = $r['diferencia'];
        Bitacora::log('turno_cerrado', 'turnos_caja', (int) $turno['id'],
            'Esperado ' . money($r['esperado']) . ', contado ' . money($contado) . ', diferencia ' . money($dif));

        $texto = abs($dif) < 0.005 ? 'La caja cuadra perfecto.'
               : ($dif < 0 ? 'Faltan ' . money(abs($dif)) . '.' : 'Sobran ' . money($dif) . '.');
        $this->listo('caja/detalle/' . (int) $turno['id'], 'Turno cerrado. ' . $texto);
    }

    /** Resumen de un turno (abierto o cerrado). El cajero solo ve los suyos. */
    public function detalle(string $id = '0'): void
    {
        $id = $this->idOr404($id);
        $t  = Caja::buscar($id);
        if (!$t) { (new ErrorController())->show404(); exit; }
        if (!Auth::is('admin') && (int) $t['usuario_id'] !== Auth::id()) {
            (new ErrorController())->show403();
            exit;
        }
        $this->view('caja/detalle', ['title' => 'Turno #' . $id, 't' => $t, 'resumen' => Caja::resumen($id)]);
    }
}
