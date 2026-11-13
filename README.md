# Karaoke Club

Sistema de gestión para un bar karaoke: ventas, caja, mesas, combos, stock y reportes.
PHP (MVC con PDO) + MySQL + Bootstrap 5 + SweetAlert2. Pensado para XAMPP.

## Instalación (XAMPP)
1. Copia la carpeta `karaoke_bar` dentro de `C:\xampp\htdocs\` (si ya tenías una versión anterior, **borra su contenido primero** y pega este: así no quedan archivos viejos. Guarda antes tu `app/config/config.php` si cambiaste la contraseña de MySQL).
2. En el panel de XAMPP inicia **Apache** y **MySQL**.
3. En `http://localhost/phpmyadmin` > **Importar**, elige `database/karaoke_club.sql`.
   Se puede importar más de una vez: no duplica nada ni borra tus datos.
4. Abre `http://localhost/karaoke_bar` (y presiona `Ctrl + F5` para limpiar el caché del navegador).

Requisitos: PHP 8.1 o superior con las extensiones `pdo_mysql` y `mbstring` (XAMPP las trae; `pdo_mysql` a veces hay que activarla quitando el `;` en php.ini).

## Usuarios iniciales
| Usuario | Contraseña | Rol |
|---------|------------|-----|
| admin   | Admin123*  | Administrador |
| cajero  | Cajero123  | Cajero (de ejemplo) |

Cambia la contraseña del admin en **Mi perfil**. Antes de usar el sistema de verdad, borra o desactiva el cajero de ejemplo y los productos, combos y mesas de ejemplo
(o importa el SQL quitando la sección "8. DATOS DE EJEMPLO").

## Qué hace cada rol
| | Administrador | Cajero |
|---|---|---|
| Vender, cobrar, entregar, anular sus pedidos | Sí (anula los de cualquiera) | Sí (anula los suyos) |
| Abrir/cerrar mesas, ver cuentas | Sí | Sí |
| Mi caja (abrir y cerrar turno) | Sí | Sí |
| Productos, categorías, stock | Sí | No |
| Combos | Sí | No |
| Crear y manejar usuarios | Sí | No |
| Reportes y descarga a Excel | Sí | No |
| Configuración | Sí | No |

## Los módulos
- **Panel**: estado de las mesas, caja del día por método de pago, stock bajo y accesos rápidos.
- **Vender**: productos y combos, mesa o mostrador, pago en Efectivo / Yape / Plin / Tarjeta, vuelto, ticket imprimible. Se cobra primero y se entrega después.
  El stock se descuenta al cobrar. Protegido contra doble clic y contra dos cajeros vendiendo la misma última unidad.
- **Anular**: pedidos cobrados y aún no entregados, con motivo; devuelve el stock exacto. No se anula lo entregado ni lo de un turno ya cerrado.
- **Mesas**: abrir mesa (cuenta), ver pedidos, cerrar mesa (no deja cerrar con pedidos sin entregar).
- **Mi caja**: turno con fondo inicial, resumen por método de pago, cierre contando el efectivo (obliga a explicar si falta o sobra).
- **Productos / Categorías / Combos**: precios, stock mínimo, entradas/salidas/ajustes con historial y combos con vigencia y ahorro.
- **Reportes**: por día, método de pago, lo más vendido, por cajero, cierres de caja con faltantes y sobrantes; descarga a Excel (CSV).
- **Configuración**: nombre del local.

## Seguridad incluida
Contraseñas con `password_hash`, consultas preparadas (PDO), token CSRF en todos los formularios, cookies de sesión HttpOnly/SameSite,
cierre de sesión por inactividad, usuarios desactivados pierden el acceso al instante,
carpeta `app/` inaccesible desde el navegador, textos escapados contra código malicioso, transacciones en todo lo que mueve dinero o stock.

## Respaldo
Todo está en la base de datos `karaoke_club`. En phpMyAdmin: entra a la base > **Exportar** > Continuar. Guarda ese archivo `.sql` (ideal: cada día).

## Estructura
```
app/config/      config.php (base de datos, sesión), menu.php (menú por rol)
app/core/        Router, Database (PDO), Auth, Csrf, Session, Validar, helpers
app/controllers/ un controlador por módulo
app/models/      consultas a la base de datos
app/views/       pantallas (layouts, y una carpeta por módulo)
database/        karaoke_club.sql
public/          index.php (único punto de entrada), assets/css, assets/js
```
Para agregar un módulo: crea el controlador (empieza con `$this->requireAuth([...])`), el modelo y la vista, y activa la opción en `app/config/menu.php`.

## Antes de usarlo en producción
En `app/config/config.php` cambia `APP_ENV` a `'production'` para ocultar los detalles de los errores.
