<?php
class AuthController extends Controller
{
    public function login(): void
    {
        if (Auth::check()) redirect('dashboard');

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            Csrf::verifyOrFail();
            $usuario  = trim((string) ($_POST['usuario'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            if ($usuario === '' || $password === '') {
                with_old(['usuario' => $usuario]);
                flash('error', 'Escribe tu usuario y tu contraseña.');
                redirect('auth/login');
            }

            [$ok, $msg] = Auth::attempt($usuario, $password);
            if (!$ok) {
                with_old(['usuario' => $usuario]);
                flash('error', $msg);
                redirect('auth/login');
            }
            redirect('dashboard');
        }

        $this->view('auth/login', ['title' => 'Ingresar'], 'auth');
    }

    public function logout(): void
    {
        $this->requirePost();
        Auth::logout();
        flash('success', 'Sesión cerrada.');
        redirect('auth/login');
    }
}
