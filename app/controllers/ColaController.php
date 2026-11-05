<?php
class ColaController extends Controller
{
    public function __construct()
    {
        $this->requireAuth();   // admin y cajero
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
            'mesas'      => $mesas,
            'sesionPre'  => $pre,
            'max'        => Cola::maximoPorMesa(),
        ]);
    }

    /** El personal agrega una canción en nombre de una mesa. */
    public function agregar(): void
    {
        $this->requirePost();
        $sesionId = (int) post('sesion_id');
        $ruta     = 'cola' . ($sesionId > 0 ? '?sesion=' . $sesionId : '');

        if ($sesionId <= 0) $this->fallar($ruta, 'Elige la mesa para la que es la canción.');
        $id = Youtube::idDeEnlace(post('enlace'));
        if ($id === null) $this->fallar($ruta, 'Ese enlace no parece de YouTube.');
        $info = Youtube::info($id);
        if (!$info['ok']) $this->fallar($ruta, $info['error']);

        try {
            $cid = Cola::agregar($sesionId, $id, $info['titulo']);
        } catch (AppException $e) {
            $this->fallar($ruta, $e->getMessage());
        }
        Bitacora::log('cancion_agregada', 'cola_canciones', $cid, $info['titulo'] . ' (por el personal)');
        $this->listo($ruta, 'Canción agregada a la cola.');
    }

    public function saltar(string $id = '0'): void
    {
        $this->requirePost();
        $id = $this->idOr404($id);
        if (!Cola::saltar($id)) $this->fallar('cola', 'Esa canción ya no está en la cola.');
        Bitacora::log('cancion_saltada', 'cola_canciones', $id);
        $this->listo('cola', 'Canción saltada.');
    }

    public function quitar(string $id = '0'): void
    {
        $this->requirePost();
        $id = $this->idOr404($id);
        if (!Cola::quitar($id)) $this->fallar('cola', 'No se pudo quitar: ya está sonando o ya no está en la cola.');
        Bitacora::log('cancion_quitada', 'cola_canciones', $id);
        $this->listo('cola', 'Canción quitada de la cola.');
    }

    /** La pone de primera en la fila. */
    public function primero(string $id = '0'): void
    {
        $this->requirePost();
        $id = $this->idOr404($id);
        if (!Cola::primero($id)) $this->fallar('cola', 'No se pudo: esa canción ya no espera turno.');
        Bitacora::log('cancion_primero', 'cola_canciones', $id);
        $this->listo('cola', 'Esa canción sonará a continuación.');
    }
}