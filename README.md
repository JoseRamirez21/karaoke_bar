# Sistema Karaoke Bar

PHP (MVC con PDO) + MySQL + Bootstrap 5 + SweetAlert2. Pensado para XAMPP.

## Instalación (XAMPP)
1. Copia la carpeta `karaoke_bar` dentro de `C:\xampp\htdocs\`.
2. En el panel de XAMPP inicia **Apache** y **MySQL**.
3. Abre `http://localhost/phpmyadmin` -> **Importar** -> elige `database/karaoke_bar.sql`.
4. (Opcional, solo para ver el panel con datos) importa también `database/datos_demo.sql`.
5. Entra a `http://localhost/karaoke_bar`

Si tu MySQL tiene contraseña o usuario distinto, cámbialo en `app/config/config.php`.

## Usuarios iniciales
| Usuario | Contraseña   | Rol            |
|---------|--------------|----------------|
| admin   | Admin123*    | Administrador  |
| cajero  | Cajero123    | Cajero (solo con datos_demo.sql) |

Cambia la contraseña del admin en **Mi perfil** apenas entres. Borra el usuario `cajero` de prueba antes de usar el sistema de verdad.

## Módulos listos
| Módulo | Quién | Qué hace |
|--------|-------|----------|
| Usuarios | admin | Crear cajeros/admins, editar, desactivar (no se borran), restablecer contraseña |
| Categorías | admin | Cervezas, licores, piqueos... (no se desactiva una con productos activos) |
| Productos | admin | Precio, stock mínimo, entradas/salidas/ajustes de stock con historial |
| Salón y mesas | admin / cajero | Abrir mesa (activa su QR), ver su cuenta y cerrarla. El admin además crea/edita mesas y genera los QR |
| Mi caja | admin / cajero | Abrir turno con fondo inicial, ver lo cobrado por método de pago y cerrar contando el efectivo (avisa si falta o sobra) |

## Qué falta (próximos pasos)
Vender (carrito y cobro), entregar/anular pedidos, combos, reservas, la cola de karaoke con YouTube,
reportes y bitácora. En el menú aparecen como "Pronto".

## QR de las mesas (importante)
Los QR apuntan a la dirección que guardes en **Salón y mesas > Imprimir QR**. Los celulares no pueden abrir
`localhost`: usa la IP de la PC en el Wi-Fi del local (CMD > `ipconfig` > Dirección IPv4), por ejemplo
`http://192.168.1.50/karaoke_bar`. Los clientes deben estar en ese mismo Wi-Fi, y el Firewall de Windows debe
permitir a Apache (puerto 80) en redes privadas. La página de impresión carga la librería de QR por internet.

## Estructura
```
app/
  config/      config.php (BD, sesión), menu.php (menú por rol)
  core/        Router, Database (PDO), Auth, Csrf, Session, Bitacora, helpers
  controllers/ Auth, Dashboard, Perfil, Usuarios, Categorias, Productos, Mesas, Caja, Error
  models/      Usuario, Categoria, Producto, Mesa, SesionMesa, Caja, Estadistica, Config
  views/       layouts (main, auth), auth, dashboard, perfil, errors
database/      karaoke_bar.sql, datos_demo.sql
public/        index.php (único punto de entrada), assets/css, assets/js
```

## Cómo agregar un módulo nuevo
1. `app/controllers/ProductosController.php` (extiende `Controller`; empieza con `$this->requireAuth(['admin']);`).
2. `app/models/Producto.php` con consultas PDO preparadas.
3. `app/views/productos/index.php`.
4. En `app/config/menu.php` cambia `'ready' => false` a `true`.
La URL `/productos/index` se enruta sola.

## Seguridad incluida
Contraseñas con `password_hash`, consultas preparadas, token CSRF en todos los POST, sesión con cookie HttpOnly/SameSite,
bloqueo tras 5 intentos fallidos (5 min), cierre por inactividad (4 h), validación de usuario activo en cada petición,
`app/` inaccesible desde el navegador y bitácora de accesos.
