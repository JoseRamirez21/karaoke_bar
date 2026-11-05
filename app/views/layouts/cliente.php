<!doctype html>
<html lang="es" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Karaoke') ?> | <?= e(Config::get('nombre_local', 'Karaoke Bar')) ?></title>
  <?php include APP_PATH . '/views/layouts/_head.php'; ?>
</head>
<body class="cliente-body">
  <main class="cliente-wrap"><?= $content ?></main>
  <script>window.__FLASH__ = <?= json_encode($flash, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;</script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= asset('assets/js/app.js') ?>"></script>
  <script src="<?= asset('assets/js/musica.js') ?>"></script>
</body>
</html>