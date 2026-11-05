<?php
declare(strict_types=1);

// ---- Base de datos (valores por defecto de XAMPP) ----
const DB_HOST = 'localhost';
const DB_NAME = 'karaoke_bar';
const DB_USER = 'root';
const DB_PASS = '';

// ---- Aplicación ----
const APP_ENV          = 'development';   // cambiar a 'production' al publicar
const SESSION_NAME     = 'KARAOKE_SESSION';
const SESSION_IDLE_MIN = 240;             // minutos de inactividad antes de cerrar sesión
const MAX_INTENTOS     = 5;               // intentos de login fallidos permitidos...
const BLOQUEO_MINUTOS  = 5;               // ...dentro de esta ventana de minutos
const DIA_INICIO_HORA  = 6;               // el "día" del negocio empieza a las 6 a. m. (el bar cierra de madrugada)

// ---- YouTube: lee el título de un video a partir de su enlace (gratis, no necesita clave)
const YOUTUBE_OEMBED = 'https://www.youtube.com/oembed';

date_default_timezone_set('America/Lima');
