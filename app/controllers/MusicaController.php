<?php
/** Lo que ve el cliente al escanear el QR de su mesa. No pide login: la mesa se identifica con el código secreto del QR. */
class MusicaController extends Controller
{
    /** La página del celular. */
    public function mesa(string $token = ''): void
    {
        [$mesa, $sesion] = $this->resolver($token);
        $mias = [];
        if ($sesion) {
            $e = $this->datos($mesa, $sesion);
            $mias = $e['mias'];
        }
        $this->view('musica/mesa', [
            'title'  => 'Elige tu música',
            'token'  => $token,
            'mesa'   => $mesa,
            'abierta'=> (bool) $sesion,
            'mias'   => $mias,
            'max'    => Cola::maximoPorMesa(),
            'ahora'  => Cola::ahora(),
        ], 'cliente');
    }

    /** Estado en vivo (el celular lo consulta cada pocos segundos). */
    public function estado(string $token = ''): void
    {
        [$mesa, $sesion] = $this->resolver($token, true);
        $ahora = Cola::ahora();
        $datos = $sesion ? $this->datos($mesa, $sesion) : ['mias' => [], 'usadas' => 0];
        $this->json([
            'abierta' => (bool) $sesion,
            'mesa'    => $mesa['numero'],
            'ahora'   => $ahora ? ['titulo' => $ahora['titulo'], 'mesa' => $ahora['mesa']] : null,
            'en_cola' => count(Cola::pendientes(500)),
            'mias'    => $datos['mias'],
            'usadas'  => $datos['usadas'],
            'max'     => Cola::maximoPorMesa(),
        ]);
    }

    public function agregar(string $token = ''): void
    {
        [$mesa, $sesion] = $this->resolver($token);
        $this->postSeguro($token);

        if (!$sesion) $this->volver($token, 'error', 'Esta mesa no está abierta. Pídele al personal que la abra.');
        if (time() - (int) ($_SESSION['musica_ultimo'] ?? 0) < 3) $this->volver($token, 'error', 'Espera unos segundos antes de agregar otra canción.');

        $id = Youtube::idDeEnlace(post('enlace'));
        if ($id === null) $this->volver($token, 'error', 'Ese enlace no parece de YouTube. En YouTube toca Compartir, Copiar enlace, y pégalo aquí.');

        $info = Youtube::info($id);
        if (!$info['ok']) $this->volver($token, 'error', $info['error']);

        try {
            Cola::agregar((int) $sesion['id'], $id, $info['titulo']);
        } catch (AppException $e) {
            $this->volver($token, 'error', $e->getMessage());
        }
        $_SESSION['musica_ultimo'] = time();
        $this->volver($token, 'success', '¡Listo! Tu canción entró a la cola.');
    }

    /** El cliente quita una de SUS canciones que aún no suena. */
    public function quitar(string $token = ''): void
    {
        [$mesa, $sesion] = $this->resolver($token);
        $this->postSeguro($token);
        if (!$sesion) $this->volver($token, 'error', 'Esta mesa no está abierta.');

        if (Cola::quitar((int) post('id'), (int) $sesion['id'])) $this->volver($token, 'success', 'Canción quitada.');
        $this->volver($token, 'error', 'No se pudo quitar: ya está sonando o ya no está en la cola.');
    }

    // ------------------------------------------------------------------
    /** Busca la mesa por el código del QR y su cuenta abierta (si la tiene). */
    private function resolver(string $token, bool $enJson = false): array
    {
        $mesa = null;
        if (preg_match('/^[a-f0-9]{32}$/', $token)) {
            $mesa = Database::one("SELECT id, numero, estado FROM mesas WHERE qr_token = ? AND estado <> 'inactiva'", [$token]);
        }
        if (!$mesa) {
            if ($enJson) $this->json(['error' => 'QR no válido'], 404);
            (new ErrorController())->show404();
            exit;
        }
        return [$mesa, SesionMesa::abierta((int) $mesa['id'])];
    }

    /** Las canciones de esta mesa (sonando y esperando) con su puesto en la fila general. */
    private function datos(array $mesa, array $sesion): array
    {
        $sid   = (int) $sesion['id'];
        $mias  = [];
        $ahora = Cola::ahora();
        if ($ahora && (int) $ahora['sesion_mesa_id'] === $sid) {
            $mias[] = ['id' => (int) $ahora['id'], 'titulo' => $ahora['titulo'], 'miniatura' => $ahora['miniatura'], 'sonando' => true, 'posicion' => 0];
        }
        foreach (Cola::pendientes(500) as $i => $c) {
            if ((int) $c['sesion_mesa_id'] === $sid) {
                $mias[] = ['id' => (int) $c['id'], 'titulo' => $c['titulo'], 'miniatura' => $c['miniatura'], 'sonando' => false, 'posicion' => $i + 1];
            }
        }
        return ['mias' => $mias, 'usadas' => Cola::usadas($sid)];
    }

    private function postSeguro(string $token): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit('Método no permitido');
        }
        if (!Csrf::valid($_POST['_csrf'] ?? null)) $this->volver($token, 'error', 'La página venció. Vuelve a intentarlo.');
    }

    private function volver(string $token, string $tipo, string $mensaje): never
    {
        flash($tipo, $mensaje);
        redirect('musica/mesa/' . $token);
    }
}