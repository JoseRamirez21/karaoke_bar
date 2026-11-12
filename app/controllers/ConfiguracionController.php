<?php
class ConfiguracionController extends Controller
{
    public function __construct()
    {
        $this->requireAuth(['admin']);
    }

    public function index(): void
    {
        $this->view('configuracion/index', [
            'title'  => 'Configuración',
            'nombre' => Config::get('nombre_local', 'Karaoke Bar'),
            'maximo' => Cola::maximoPorMesa(),
        ]);
    }

    public function guardar(): void
    {
        $this->requirePost();
        $nombre = post('nombre_local');
        $maximo = Validar::entero(post('max_canciones_mesa'));

        if (!Validar::largo($nombre, 2, 60))        $this->fallar('configuracion', 'El nombre del local debe tener entre 2 y 60 caracteres.');
        if ($maximo === null || $maximo < 1 || $maximo > 50) $this->fallar('configuracion', 'El máximo de canciones por mesa debe ser un número entre 1 y 50.');

        Config::set('nombre_local', $nombre);
        Config::set('max_canciones_mesa', (string) $maximo);
        Bitacora::log('configuracion_cambiada', 'configuracion', null, "Local: $nombre, canciones por mesa: $maximo");
        $this->listo('configuracion', 'Configuración guardada.');
    }
}
