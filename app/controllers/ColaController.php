<?php
class ColaController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();   // solo el personal: admin y cajero
    }

    public function index(): void
    {
        $mesas = Venta::mesasAbiertas();
        $pre   = (int) ($_GET['sesion'] ?? 0);
        if (!in_array($pre, array_map('intval', array_column($mesas, 'sesion_id')), true)) $pre = 0;

        $this->view('cola/index', [
            'title'      => 'Cola de karaoke',
            'ahora'      => Cola::ahora(),
            'pendientes' => Cola::pendientes(),
            'recientes'  => Cola::recientes(),
            'mesas'      => $mesas,
            'sesionPre'  => $pre,
            'max'        => Cola::maximoPorMesa(),
        ]);
    }

    /** Anota una canción para una mesa. */
    public function agregar(): void
    {
        $this->requirePost();
        $sesionId = (int) post('sesion_id');
        $ruta     = 'cola' . ($sesionId > 0 ? '?sesion=' . $sesionId : '');
        $titulo   = trim(preg_replace('/[\x00-\x1F\x7F\s]+/u', ' ', post('titulo')) ?? '');

        if ($sesionId <= 0)                   $this->fallar($ruta, 'Elige la mesa para la que es la canción.');
        if (!Validar::largo($titulo, 2, 150)) $this->fallar($ruta, 'Escribe el nombre de la canción (y el artista si quieres), entre 2 y 150 letras.');

        try {
            $id = Cola::agregar($sesionId, $titulo);
        } catch (AppException $e) {
            $this->fallar($ruta, $e->getMessage());
        }
        Bitacora::log('cancion_agregada', 'cola_canciones', $id, $titulo);
        $this->listo($ruta, 'Canción anotada en la fila.');
    }

    /** "Siguiente": termina la que cantaba y pone a cantar la próxima. */
    public function avanzar(): void
    {
        $this->requirePost();
        $ahora = Cola::avanzar();
        Bitacora::log('cancion_siguiente', 'cola_canciones', $ahora ? (int) $ahora['id'] : null, $ahora ? $ahora['titulo'] : 'fila vacía');
        $this->listo('cola', $ahora ? 'Ahora canta la mesa ' . $ahora['mesa'] . ': ' . $ahora['titulo'] : 'No quedan canciones en la fila.');
    }

    /** La que cantaba terminó (sin poner otra). */
    public function terminar(): void
    {
        $this->requirePost();
        if (!Cola::terminar()) $this->fallar('cola', 'No hay ninguna canción cantando.');
        Bitacora::log('cancion_terminada', 'cola_canciones');
        $this->listo('cola', 'Canción terminada.');
    }

    public function saltar(string $id = '0'): void
    {
        $this->requirePost();
        $id = $this->idOr404($id);
        if (!Cola::saltar($id)) $this->fallar('cola', 'Esa canción ya no está en la fila.');
        Bitacora::log('cancion_saltada', 'cola_canciones', $id);
        $this->listo('cola', 'Canción saltada.');
    }

    public function quitar(string $id = '0'): void
    {
        $this->requirePost();
        $id = $this->idOr404($id);
        if (!Cola::quitar($id)) $this->fallar('cola', 'No se pudo quitar: ya está cantando o ya no está en la fila.');
        Bitacora::log('cancion_quitada', 'cola_canciones', $id);
        $this->listo('cola', 'Canción quitada de la fila.');
    }

    /** La pone de primera en la fila. */
    public function primero(string $id = '0'): void
    {
        $this->requirePost();
        $id = $this->idOr404($id);
        if (!Cola::primero($id)) $this->fallar('cola', 'No se pudo: esa canción ya no espera turno.');
        Bitacora::log('cancion_primero', 'cola_canciones', $id);
        $this->listo('cola', 'Esa canción cantará a continuación.');
    }
}
