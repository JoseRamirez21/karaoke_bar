<?php
class UsuariosController extends Controller
{
    public function __construct()
    {
        $this->requireAuth(['admin']);
    }

    public function index(): void
    {
        $this->view('usuarios/index', ['title' => 'Usuarios', 'usuarios' => Usuario::todos()]);
    }

    public function crear(): void
    {
        $this->view('usuarios/form', ['title' => 'Nuevo usuario', 'modo' => 'crear', 'u' => [], 'roles' => Usuario::roles()]);
    }

    public function guardar(): void
    {
        $this->requirePost();
        $d    = $this->datos();
        $pass = (string) ($_POST['password'] ?? '');

        $err = $this->validar($d, 0) ?? Validar::password($pass);
        if ($err) $this->fallar('usuarios/crear', $err);

        $id = Usuario::crear($d + ['password' => password_hash($pass, PASSWORD_DEFAULT)]);
        $this->listo('usuarios', 'Usuario creado. Entrégale su usuario y contraseña en persona.');
    }

    public function editar(string $id = '0'): void
    {
        $id = $this->idOr404($id);
        $u  = Usuario::buscarPorId($id) ?? $this->noExiste();
        $this->view('usuarios/form', ['title' => 'Editar usuario', 'modo' => 'editar', 'u' => $u, 'roles' => Usuario::roles()]);
    }

    public function actualizar(string $id = '0'): void
    {
        $this->requirePost();
        $id = $this->idOr404($id);
        $u  = Usuario::buscarPorId($id) ?? $this->noExiste();

        $d = $this->datos();
        if ($id === Auth::id()) $d['rol_id'] = (int) $u['rol_id']; 

        $err = $this->validar($d, $id);
        if ($err) $this->fallar("usuarios/editar/$id", $err);

        Usuario::actualizar($id, $d);
        $this->listo('usuarios', 'Cambios guardados.');
    }

    public function estado(string $id = '0'): void
    {
        $this->requirePost();
        $id = $this->idOr404($id);
        $u  = Usuario::buscarPorId($id) ?? $this->noExiste();

        if ($id === Auth::id()) {
            flash('error', 'No puedes desactivar tu propia cuenta.');
            redirect('usuarios');
        }
        $nuevo = (int) $u['estado'] === 1 ? 0 : 1;
        Usuario::setEstado($id, $nuevo);
        $this->listo('usuarios', $nuevo ? 'Usuario activado.' : 'Usuario desactivado. Ya no podrá ingresar.');
    }

    public function password(string $id = '0'): void
    {
        $this->requirePost();
        $id = $this->idOr404($id);
        $u  = Usuario::buscarPorId($id) ?? $this->noExiste();

        $pass = (string) ($_POST['password'] ?? '');
        $err  = Validar::password($pass);
        if ($err) $this->fallar("usuarios/editar/$id", $err);

        Usuario::actualizarPassword($id, password_hash($pass, PASSWORD_DEFAULT));
        $this->listo("usuarios/editar/$id", 'Contraseña restablecida. Entrégasela al trabajador en persona.');
    }

    private function datos(): array
    {
        return [
            'nombre'   => post('nombre'),
            'usuario'  => strtolower(post('usuario')),
            'telefono' => post('telefono'),
            'rol_id'   => (int) post('rol_id'),
        ];
    }

    private function validar(array $d, int $exceptoId): ?string
    {
        if (!Validar::largo($d['nombre'], 2, 100))                  return 'Escribe el nombre completo (entre 2 y 100 letras).';
        if (!preg_match('/^[a-z0-9._-]{3,30}$/', $d['usuario']))    return 'El usuario debe tener de 3 a 30 caracteres: letras, números, punto, guion o guion bajo, sin espacios.';
        if ($d['telefono'] !== '' && !preg_match('/^[0-9+() -]{6,20}$/', $d['telefono'])) return 'El teléfono no es válido.';
        if (!in_array($d['rol_id'], array_column(Usuario::roles(), 'id'), false)) return 'Elige un rol.';
        if (Usuario::usuarioEnUso($d['usuario'], $exceptoId))       return 'Ese usuario ya existe. Elige otro.';
        return null;
    }

    private function noExiste(): never
    {
        (new ErrorController())->show404();
        exit;
    }
}
