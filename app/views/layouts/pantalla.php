<!doctype html>
<html lang="es" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Pantalla') ?> | <?= e(Config::get('nombre_local', 'Karaoke Bar')) ?></title>
  <?php include APP_PATH . '/views/layouts/_head.php'; ?>
</head>
<body class="tv-body">
  <?= $content ?>
  <script src="<?= asset('assets/js/pantalla.js') ?>"></script>
</body>
</html>