<?php
$ocupadas   = count(array_filter($salon, fn($m) => $m['estado'] === 'ocupada'));
$reservadas = count(array_filter($salon, fn($m) => $m['estado'] === 'reservada'));
$maxMetodo  = 0.0;
if ($ventas) foreach ($ventas['metodos'] as $m) $maxMetodo = max($maxMetodo, (float) $m['total']);
$nombre1 = explode(' ', trim(Auth::user()['nombre']))[0];
?>
<header class="page-head">
  <h1><?= e(saludo()) ?>, <?= e($nombre1) ?></h1>
  <p><?= e(fecha_larga()) ?></p>
</header>

<?php if (!empty($_SESSION['pass_default'])): ?>
  <div class="notice notice--warn" role="alert">
    <i class="bi bi-shield-exclamation"></i>
    <span>Sigues usando la contraseña inicial. Cámbiala para proteger las ventas.</span>
    <a class="btn btn-sm btn-brass" href="<?= url('perfil') ?>">Cambiar contraseña</a>
  </div>
<?php endif; ?>

<div class="row g-4 mb-4">
  <!-- SALÓN -->
  <div class="col-xl-8">
    <section class="panel panel--stage h-100">
      <div class="panel__head">
        <h2 class="panel__title">Salón</h2>
        <?php if ($salon): ?>
          <span class="panel__meta"><?= $ocupadas ?> de <?= count($salon) ?> mesas ocupadas<?= $reservadas ? ', ' . $reservadas . ' reservada' . ($reservadas > 1 ? 's' : '') : '' ?></span>
        <?php endif; ?>
      </div>

      <?php if (!$salon): ?>
        <p class="empty">Aún no hay mesas registradas. Cuando las crees, cada una aparecerá aquí con su estado.</p>
      <?php else: ?>
        <div class="floor">
          <?php foreach ($salon as $m): ?>
            <div class="mesa mesa--<?= e($m['estado']) ?>" title="Mesa <?= e($m['numero']) ?>, para <?= (int) $m['capacidad'] ?> personas">
              <span class="mesa__n"><?= e($m['numero']) ?></span>
              <span class="mesa__e"><?= ['libre' => 'Libre', 'ocupada' => 'Ocupada', 'reservada' => 'Reservada'][$m['estado']] ?? e($m['estado']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
        <ul class="legend">
          <li><i class="dot dot--libre"></i>Libre</li>
          <li><i class="dot dot--ocupada"></i>Ocupada</li>
          <li><i class="dot dot--reservada"></i>Reservada</li>
        </ul>
      <?php endif; ?>
    </section>
  </div>

  <!-- CAJA -->
  <div class="col-xl-4">
    <section class="panel h-100">
      <div class="panel__head">
        <h2 class="panel__title"><?= $esAdmin ? 'Caja del día' : 'Tu turno' ?></h2>
      </div>

      <?php if ($ventas === null): ?>
        <p class="empty">No tienes un turno abierto. Abre tu caja antes de empezar a cobrar.</p>
        <a class="btn btn-brass" href="<?= url('caja') ?>">Abrir caja</a>
      <?php else: ?>
        <p class="big-figure"><?= e(money($ventas['total'])) ?></p>
        <p class="text-secondary mb-4">
          <?= (int) $ventas['n'] ?> venta<?= $ventas['n'] === 1 ? '' : 's' ?>
          <?= $esAdmin ? 'desde las ' . e(hora12($desde)) : 'en este turno' ?>
        </p>
        <ul class="bars">
          <?php foreach ($ventas['metodos'] as $m):
              $pct = $maxMetodo > 0 ? round(((float) $m['total'] / $maxMetodo) * 100) : 0; ?>
            <li>
              <div class="bars__row"><span><?= e($m['nombre']) ?></span><strong><?= e(money($m['total'])) ?></strong></div>
              <div class="bars__track"><div class="bars__fill" style="width: <?= $pct ?>%"></div></div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>
</div>

<div class="row g-4">
  <!-- COLA -->
  <div class="col-lg-4">
    <section class="panel h-100">
      <div class="panel__head">
        <h2 class="panel__title">Cola de karaoke</h2>
        <?php if ($colaTotal): ?><span class="panel__meta"><?= $colaTotal ?> en cola</span><?php endif; ?>
      </div>
      <?php if (!$cola): ?>
        <p class="empty">Nadie ha pedido canciones todavía. Las mesas abiertas piden música desde su QR.</p>
      <?php else: ?>
        <ol class="queue">
          <?php foreach ($cola as $c): ?>
            <li class="<?= $c['estado'] === 'reproduciendo' ? 'is-playing' : '' ?>">
              <span class="queue__mesa">Mesa <?= e($c['mesa']) ?></span>
              <span class="queue__title"><?= e($c['titulo']) ?></span>
              <?php if ($c['estado'] === 'reproduciendo'): ?><span class="queue__now">Suena ahora</span><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </section>
  </div>

  <!-- RESERVAS -->
  <div class="col-lg-4">
    <section class="panel h-100">
      <div class="panel__head"><h2 class="panel__title">Próximas reservas</h2></div>
      <?php if (!$reservas): ?>
        <p class="empty">No hay reservas para las próximas 24 horas.</p>
      <?php else: ?>
        <ul class="rows">
          <?php foreach ($reservas as $r): ?>
            <li>
              <div><strong>Mesa <?= e($r['mesa']) ?></strong><span><?= e($r['cliente']) ?>, <?= (int) $r['personas'] ?> personas</span></div>
              <time><?= e(hora12($r['fecha_hora'])) ?></time>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>

  <!-- STOCK -->
  <div class="col-lg-4">
    <section class="panel h-100">
      <div class="panel__head">
        <h2 class="panel__title">Stock bajo</h2>
        <?php if ($stockBajoTotal): ?><span class="panel__meta"><?= $stockBajoTotal ?> producto<?= $stockBajoTotal > 1 ? 's' : '' ?></span><?php endif; ?>
      </div>
      <?php if (!$stockBajo): ?>
        <p class="empty">Todo el stock está por encima de su mínimo.</p>
      <?php else: ?>
        <ul class="rows">
          <?php foreach ($stockBajo as $p): ?>
            <li>
              <div><strong><?= e($p['nombre']) ?></strong><span>Mínimo <?= (int) $p['stock_minimo'] ?></span></div>
              <span class="stock-pill<?= (int) $p['stock'] === 0 ? ' is-zero' : '' ?>"><?= (int) $p['stock'] === 0 ? 'Agotado' : 'Quedan ' . (int) $p['stock'] ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>
</div>
