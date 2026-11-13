<?php
declare(strict_types=1);

// ---- Base de datos (valores por defecto de XAMPP) ----
const DB_HOST = 'localhost';
const DB_NAME = 'karaoke_club';
const DB_USER = 'root';
const DB_PASS = '';

// ---- Aplicación ----
const APP_ENV          = 'development';   // cambiar a 'production' al publicar
const SESSION_NAME     = 'KARAOKE_SESSION';
const SESSION_IDLE_MIN = 240;             // minutos de inactividad antes de cerrar sesión
const DIA_INICIO_HORA  = 6;               // el "día" del negocio empieza a las 6 a. m. (el bar cierra de madrugada)

date_default_timezone_set('America/Lima');
