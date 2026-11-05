<?php $selSesion = (int) (($GLOBALS['_old']['sesion_id'] ?? null) ?: $sesionPre); ?>
<header class="page-head">
  <h1>Cola de karaoke</h1>
  <p>Lo que suena y lo que viene. Cada mesa puede tener hasta <?= (int) $max ?> canciones en la fila.</p>
</header>

<section class="panel mb-4 ahora">
  <h2 class="panel__title mb-3">Ahora suena</h2>
  <?php if (!$ahora): ?>
    <p class="empty mb-0">No hay ninguna canción sonando. Cuando la pantalla del karaoke esté abierta, empezará con la primera de la fila.</p>
  <?php else: ?>
    <div class="cancion">
      <img src="<?= e($ahora['miniatura']) ?>" alt="">
      <div class="cancion__t"><strong><?= e($ahora['titulo']) ?></strong><span>Mesa <?= e($ahora['mesa']) ?></span></div>
      <form method="post" action="<?= url('cola/saltar/' . (int) $ahora['id']) ?>" onsubmit="return confirm('¿Saltar esta canción?');">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-outline-brass" type="submit">Saltar</button>
      </form>
    </div>
  <?php endif; ?>
</section>

<div class="row g-4">
  <div class="col-lg-8">
    <section class="panel">
      <div class="panel__head">
        <h2 class="panel__title">Siguientes</h2>
        <span class="panel__meta"><?= count($pendientes) ?> en la fila</span>
      </div>
      <?php if (!$pendientes): ?>
        <p class="empty mb-0">La fila está vacía. Los clientes piden desde el QR de su mesa, o tú puedes agregar una canción aquí.</p>
      <?php else: foreach ($pendientes as $i => $c): ?>
        <div class="cancion">
          <span class="cancion__n"><?= $i + 1 ?></span>
          <img src="<?= e($c['miniatura']) ?>" alt="" loading="lazy">
          <div class="cancion__t"><strong><?= e($c['titulo']) ?></strong><span>Mesa <?= e($c['mesa']) ?></span></div>
          <div class="d-flex gap-1">
            <?php if ($i > 0): ?>
              <form method="post" action="<?= url('cola/primero/' . (int) $c['id']) ?>"><?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-field" type="submit" title="Poner de primera">Primero</button></form>
            <?php endif; ?>
            <form method="post" action="<?= url('cola/quitar/' . (int) $c['id']) ?>" onsubmit="return confirm('¿Quitar esta canción de la fila?');"><?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-field" type="submit">Quitar</button></form>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </section>
  </div>

  <div class="col-lg-4">
    <section class="panel">
      <h2 class="panel__title mb-1">Agregar una canción</h2>
      <p class="text-secondary">Para una mesa que no puede hacerlo desde su celular.</p>
      <?php if (!$mesas): ?>
        <p class="empty mb-0">No hay mesas abiertas todavía.</p>
      <?php else: ?>
        <form method="post" action="<?= url('cola/agregar') ?>" autocomplete="off">
          <?= csrf_field() ?>
          <label class="form-label" for="sesion_id">Mesa</label>
          <select class="form-select mb-3" id="sesion_id" name="sesion_id" required>
            <option value="">Elige...</option>
            <?php foreach ($mesas as $m): ?>
              <option value="<?= (int) $m['sesion_id'] ?>"<?= sel($selSesion, $m['sesion_id']) ?>>Mesa <?= e($m['numero']) ?><?= $m['cliente'] ? ' (' . e($m['cliente']) . ')' : '' ?></option>
            <?php endforeach; ?>
          </select>
          <label class="form-label" for="enlace">Enlace de YouTube</label>
          <input class="form-control mb-3" id="enlace" name="enlace" inputmode="url" maxlength="300" required placeholder="https://youtu.be/...">
          <button class="btn btn-brass w-100" type="submit">Agregar</button>
        </form>
      <?php endif; ?>
    </section>
  </div>
</div>

<script>
  // Se actualiza sola cada 8 segundos, salvo que estés escribiendo.
  setInterval(function () {
    var a = document.activeElement;
    if (a && (a.tagName === 'INPUT' || a.tagName === 'SELECT')) return;
    var campo = document.getElementById('enlace');
    if (campo && campo.value) return;
    location.reload();
  }, 8000);
</script>