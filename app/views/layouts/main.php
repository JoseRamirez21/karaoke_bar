<?php
$menu  = require APP_PATH . '/config/menu.php';
$user  = Auth::user();
$local = Config::get('nombre_local', 'Karaoke Bar');
$inicial = mb_strtoupper(mb_substr($user['nombre'] ?? '?', 0, 1));
?>
<!doctype html>
<html lang="es" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Panel') ?> | <?= e($local) ?></title>
  <?php include APP_PATH . '/views/layouts/_head.php'; ?>
</head>
<body>
<header class="topbar d-lg-none">
  <button class="btn btn-icon" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-label="Abrir menú">
    <i class="bi bi-list fs-3"></i>
  </button>
  <span class="brand-name"><?= e($local) ?></span>
</header>

<div class="shell">
  <aside class="sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar" aria-label="Menú principal">
    <div class="sidebar__brand">
      <span class="brand-mark"><i class="bi bi-mic-fill"></i></span>
      <span class="brand-name"><?= e($local) ?></span>
    </div>

    <nav class="sidebar__nav">
      <?php foreach ($menu as $grupo):
          $items = array_filter($grupo['items'], fn($i) => in_array(Auth::role(), $i['roles'], true));
          if (!$items) continue; ?>
        <p class="nav-group"><?= e($grupo['grupo']) ?></p>
        <?php foreach ($items as $i):
            $activo = Router::$controller === $i['route']; ?>
          <?php if ($i['ready']): ?>
            <a class="nav-item-link<?= $activo ? ' is-active' : '' ?>" href="<?= url($i['route']) ?>"<?= $activo ? ' aria-current="page"' : '' ?>>
              <i class="bi <?= e($i['icon']) ?>"></i><span><?= e($i['label']) ?></span>
            </a>
          <?php else: ?>
            <span class="nav-item-link is-soon" aria-disabled="true">
              <i class="bi <?= e($i['icon']) ?>"></i><span><?= e($i['label']) ?></span><small>Pronto</small>
            </span>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>

    <div class="sidebar__user">
      <a class="user-chip" href="<?= url('perfil') ?>" title="Mi perfil">
        <span class="avatar"><?= e($inicial) ?></span>
        <span class="user-chip__text">
          <strong><?= e($user['nombre']) ?></strong>
          <small><?= Auth::is('admin') ? 'Administrador' : 'Cajero' ?></small>
        </span>
      </a>
      <form method="post" action="<?= url('auth/logout') ?>" data-confirm="¿Cerrar sesión?" data-confirm-button="Sí, salir">
        <?= csrf_field() ?>
        <button class="btn btn-icon" type="submit" title="Cerrar sesión" aria-label="Cerrar sesión"><i class="bi bi-box-arrow-right"></i></button>
      </form>
    </div>
  </aside>

  <main class="content">
    <?= $content ?>
  </main>
</div>

<script>window.__FLASH__ = <?= json_encode($flash, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>
