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
            'nombre' => Config::get('nombre_local', 'Karaoke Club'),
        ]);
    }

    public function guardar(): void
    {
        $this->requirePost();
        $nombre = post('nombre_local');

        if (!Validar::largo($nombre, 2, 60))        $this->fallar('configuracion', 'El nombre del local debe tener entre 2 y 60 caracteres.');

        Config::set('nombre_local', $nombre);
        $this->listo('configuracion', 'Configuración guardada.');
    }
}
