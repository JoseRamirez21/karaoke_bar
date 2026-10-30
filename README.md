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

## Estructura
```
app/
  config/      config.php (BD, sesión), menu.php (menú por rol)
  core/        Router, Database (PDO), Auth, Csrf, Session, Bitacora, helpers
  controllers/ Auth, Dashboard, Perfil, Error
  models/      Usuario, Estadistica, Config
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
