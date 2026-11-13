<?php
class ReportesController extends Controller
{
    public function __construct()
    {
        $this->requireAuth(['admin']);
    }

    public function index(): void
    {
        [$desde, $hasta] = $this->fechas();
        [$ini, $fin]     = Reporte::rango($desde, $hasta);
        $turnos = Reporte::turnos($ini, $fin);

        $falta = 0.0; $sobra = 0.0;
        foreach ($turnos as $t) {
            if ($t['estado'] !== 'cerrado') continue;
            $d = (float) $t['diferencia'];
            if ($d < 0) $falta += -$d; elseif ($d > 0) $sobra += $d;
        }

        $this->view('reportes/index', [
            'title'    => 'Reportes',
            'desde'    => $desde, 'hasta' => $hasta,
            'atajos'   => $this->atajos(),
            'resumen'  => Reporte::resumen($ini, $fin),
            'dias'     => Reporte::porDia($ini, $fin),
            'metodos'  => Reporte::porMetodo($ini, $fin),
            'top'      => Reporte::topProductos($ini, $fin),
            'cajeros'  => Reporte::porCajero($ini, $fin),
            'turnos'   => $turnos,
            'falta'    => $falta, 'sobra' => $sobra,
        ]);
    }

    /** Descarga las ventas del rango en CSV (se abre en Excel). */
    public function exportar(): void
    {
        [$desde, $hasta] = $this->fechas();
        [$ini, $fin]     = Reporte::rango($desde, $hasta);
        $filas = Reporte::ventasDetalle($ini, $fin);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="ventas_' . $desde . '_a_' . $hasta . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");   // para que Excel respete las tildes
        fputcsv($out, ['Ticket', 'Fecha y hora', 'Lugar', 'Atendió', 'Pago', 'Estado', 'Total']);
        foreach ($filas as $f) {
            fputcsv($out, [
                $this->seguro($f['codigo']), $f['created_at'], $this->seguro($f['lugar'] === 'Mostrador' ? 'Mostrador' : 'Mesa ' . $f['lugar']),
                $this->seguro($f['cajero']), $this->seguro($f['metodo']), $f['estado'], number_format((float) $f['total'], 2, '.', ''),
            ]);
        }
        fclose($out);
        exit;
    }

    /** Evita que Excel ejecute como fórmula un texto que empiece con = + - @ */
    private function seguro(string $t): string
    {
        return preg_match('/^[=+\-@\t\r]/', $t) ? "'" . $t : $t;
    }

    private function hoy(): string
    {
        return date('Y-m-d', strtotime('-' . DIA_INICIO_HORA . ' hours'));
    }

    private function fechaValida(?string $f): ?string
    {
        if ($f === null || $f === '') return null;
        $d = DateTime::createFromFormat('Y-m-d', $f);
        return ($d && $d->format('Y-m-d') === $f) ? $f : null;
    }

    /** @return array{0:string,1:string} desde y hasta (por defecto, hoy), máximo un año */
    private function fechas(): array
    {
        $hoy   = $this->hoy();
        $desde = $this->fechaValida($_GET['desde'] ?? null) ?? $hoy;
        $hasta = $this->fechaValida($_GET['hasta'] ?? null) ?? $desde;
        if ($hasta < $desde) [$desde, $hasta] = [$hasta, $desde];
        if ((strtotime($hasta) - strtotime($desde)) / 86400 > 365) $hasta = date('Y-m-d', strtotime($desde . ' +365 days'));
        return [$desde, $hasta];
    }

    private function atajos(): array
    {
        $hoy = $this->hoy();
        return [
            'Hoy'            => [$hoy, $hoy],
            'Ayer'           => [date('Y-m-d', strtotime($hoy . ' -1 day')), date('Y-m-d', strtotime($hoy . ' -1 day'))],
            'Esta semana'    => [date('Y-m-d', strtotime('monday this week', strtotime($hoy))), $hoy],
            'Este mes'       => [date('Y-m-01', strtotime($hoy)), $hoy],
            'Últimos 30 días' => [date('Y-m-d', strtotime($hoy . ' -29 days')), $hoy],
        ];
    }
}
