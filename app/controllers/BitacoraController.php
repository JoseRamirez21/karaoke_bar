<?php
class BitacoraController extends Controller
{
    /** Cómo se muestra cada acción en pantalla. Las que no estén aquí se muestran tal cual. */
    public const ETIQUETAS = [
        'login' => 'Ingresó al sistema', 'logout' => 'Cerró sesión', 'login_fallido' => 'Intento de ingreso fallido', 'password_cambiada' => 'Cambió su contraseña',
        'usuario_creado' => 'Creó un usuario', 'usuario_editado' => 'Editó un usuario', 'usuario_activado' => 'Activó un usuario', 'usuario_desactivado' => 'Desactivó un usuario', 'password_restablecida' => 'Restableció una contraseña',
        'categoria_creada' => 'Creó una categoría', 'categoria_editada' => 'Editó una categoría', 'categoria_activada' => 'Activó una categoría', 'categoria_desactivada' => 'Desactivó una categoría',
        'producto_creado' => 'Creó un producto', 'producto_editado' => 'Editó un producto', 'precio_cambiado' => 'Cambió un precio', 'producto_activado' => 'Activó un producto', 'producto_desactivado' => 'Desactivó un producto',
        'stock_entrada' => 'Entrada de stock', 'stock_salida' => 'Salida de stock', 'stock_ajuste' => 'Ajuste de stock',
        'combo_creado' => 'Creó un combo', 'combo_editado' => 'Editó un combo', 'combo_activado' => 'Activó un combo', 'combo_desactivado' => 'Desactivó un combo',
        'mesa_creada' => 'Creó una mesa', 'mesa_editada' => 'Editó una mesa', 'mesa_activada' => 'Activó una mesa', 'mesa_desactivada' => 'Desactivó una mesa', 'mesa_abierta' => 'Abrió una mesa', 'mesa_cerrada' => 'Cerró una mesa',
        'reserva_creada' => 'Registró una reserva', 'reserva_editada' => 'Editó una reserva', 'reserva_confirmada' => 'Confirmó una reserva', 'reserva_cancelada' => 'Canceló una reserva', 'reserva_no_asistio' => 'Reserva: no asistió', 'reserva_cumplida' => 'Reserva cumplida',
        'turno_abierto' => 'Abrió su caja', 'turno_cerrado' => 'Cerró su caja',
        'venta_creada' => 'Cobró un pedido', 'venta_entregada' => 'Entregó un pedido', 'venta_anulada' => 'Anuló un pedido',
        'cancion_agregada' => 'Anotó una canción', 'cancion_siguiente' => 'Pasó a la siguiente canción', 'cancion_terminada' => 'Terminó una canción', 'cancion_saltada' => 'Saltó una canción', 'cancion_quitada' => 'Quitó una canción', 'cancion_primero' => 'Puso una canción de primera',
        'configuracion_cambiada' => 'Cambió la configuración', 'reporte_exportado' => 'Descargó un reporte',
    ];

    public function __construct()
    {
        $this->requireAuth(['admin']);
    }

    public function index(): void
    {
        $fecha = function (string $k): string {
            $v = trim((string) ($_GET[$k] ?? ''));
            $d = DateTime::createFromFormat('Y-m-d', $v);
            return ($d && $d->format('Y-m-d') === $v) ? $v : '';
        };
        $f = [
            'accion'     => preg_match('/^[a-z_]{1,40}$/', (string) ($_GET['accion'] ?? '')) ? $_GET['accion'] : '',
            'usuario_id' => (int) ($_GET['usuario'] ?? 0),
            'desde'      => $fecha('desde'),
            'hasta'      => $fecha('hasta'),
            'q'          => mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 60),
        ];
        $pagina = max(1, (int) ($_GET['p'] ?? 1));
        $r      = Bitacora::listar($f, $pagina, 50);

        $this->view('bitacora/index', [
            'title'     => 'Bitácora',
            'f'         => $f,
            'filas'     => $r['filas'],
            'total'     => $r['total'],
            'pagina'    => $pagina,
            'paginas'   => max(1, (int) ceil($r['total'] / 50)),
            'acciones'  => Bitacora::acciones(),
            'usuarios'  => Usuario::todos(),
            'etiquetas' => self::ETIQUETAS,
        ]);
    }
}
