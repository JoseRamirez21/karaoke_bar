<?php
class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        $esAdmin = Auth::is('admin');
        $desde   = Estadistica::inicioDiaNegocio();
        $turno   = $esAdmin ? null : Estadistica::turnoAbierto(Auth::id());

        
        $ventas = ($esAdmin || $turno)
            ? Estadistica::ventas($desde, $turno['id'] ?? null)
            : null;

        $this->view('dashboard/index', [
            'title'          => 'Panel',
            'esAdmin'        => $esAdmin,
            'desde'          => $desde,
            'turno'          => $turno,
            'ventas'         => $ventas,
            'mesas'          => Estadistica::mesas(),
            'stockBajo'      => Estadistica::stockBajo(),
            'stockBajoTotal' => Estadistica::stockBajoTotal(),
        ]);
    }
}
