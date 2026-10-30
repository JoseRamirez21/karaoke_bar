<?php
class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        $esAdmin = Auth::is('admin');
        $desde   = Estadistica::inicioDiaNegocio();
        $turno   = $esAdmin ? null : Estadistica::turnoAbierto(Auth::id());

        // El admin ve todo el día del negocio; el cajero solo lo cobrado en su turno.
        $ventas = ($esAdmin || $turno)
            ? Estadistica::ventas($desde, $turno['id'] ?? null)
            : null;

        $this->view('dashboard/index', [
            'title'          => 'Panel',
            'esAdmin'        => $esAdmin,
            'desde'          => $desde,
            'turno'          => $turno,
            'ventas'         => $ventas,
            'salon'          => Estadistica::salon(),
            'cola'           => Estadistica::cola(),
            'colaTotal'      => Estadistica::colaTotal(),
            'stockBajo'      => Estadistica::stockBajo(),
            'stockBajoTotal' => Estadistica::stockBajoTotal(),
            'reservas'       => Estadistica::reservasProximas(),
        ]);
    }
}
