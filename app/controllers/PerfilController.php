<?php
class PerfilController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $this->view('perfil/index', [
            'title'  => 'Mi perfil',
            'perfil' => Usuario::buscarPorId(Auth::id()),
        ]);
    }

    public function password(): void
    {
        $this->requireAuth();
        $this->requirePost();

        $actual  = (string) ($_POST['actual'] ?? '');
        $nueva   = (string) ($_POST['nueva'] ?? '');
        $repetir = (string) ($_POST['repetir'] ?? '');

        $u = Usuario::buscarPorId(Auth::id());

        $error = null;
        if (!$u || !password_verify($actual, $u['password']))         $error = 'La contraseña actual no es correcta.';
        elseif (mb_strlen($nueva) < 8)                                 $error = 'La nueva contraseña debe tener al menos 8 caracteres.';
        elseif (!preg_match('/[A-Za-z]/', $nueva) || !preg_match('/\d/', $nueva)) $error = 'Usa al menos una letra y un número.';
        elseif ($nueva === $actual || $nueva === 'Admin123*')          $error = 'Elige una contraseña distinta a la anterior.';
        elseif ($nueva !== $repetir)                                   $error = 'Las contraseñas nuevas no coinciden.';

        if ($error) {
            flash('error', $error);
            redirect('perfil');
        }

        Usuario::actualizarPassword(Auth::id(), password_hash($nueva, PASSWORD_DEFAULT));
        Bitacora::log('password_cambiada', 'usuarios', Auth::id());
        session_regenerate_id(true);
        $_SESSION['pass_default'] = false;
        flash('success', 'Contraseña actualizada.');
        redirect('perfil');
    }
}
