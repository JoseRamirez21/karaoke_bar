<!doctype html>
<html lang="es" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Ingresar') ?> | <?= e(Config::get('nombre_local', 'Karaoke Club')) ?></title>
  <?php include APP_PATH . '/views/layouts/_head.php'; ?>
</head>
<body class="auth-body">
  <main class="auth-wrap">
    <?php foreach ($flash as $f):
        $cls = ['error' => 'danger', 'warning' => 'warning', 'success' => 'success'][$f['type']] ?? 'info'; ?>
      <div class="alert alert-<?= $cls ?> auth-alert" role="alert"><?= e($f['msg']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>
