<?php $selSesion = (int) (($GLOBALS['_old']['sesion_id'] ?? null) ?: $sesionPre); ?>
<header class="page-head">
  <h1>Cola de karaoke</h1>
  <p>Anota las canciones que piden las mesas. La fila se ordena sola para que las mesas se turnen (máximo <?= (int) $max ?> canciones por mesa).</p>
</header>

<section class="panel mb-4 ahora">
  <div class="panel__head">
    <h2 class="panel__title">Cantando ahora</h2>
    <?php if ($pendientes || $ahora): ?>
      <form method="post" action="<?= url('cola/avanzar') ?>"><?= csrf_field() ?>
        <button class="btn btn-brass" type="submit"><?= $ahora ? 'Terminó, siguiente' : 'Empezar con la primera' ?> <i class="bi bi-skip-forward-fill"></i></button>
      </form>
    <?php endif; ?>
  </div>
  <?php if (!$ahora): ?>
    <p class="empty mb-0">Nadie está cantando. <?= $pendientes ? 'Pulsa "Empezar con la primera" cuando sea el turno.' : 'Anota una canción para empezar.' ?></p>
  <?php else: ?>
    <div class="cancion">
      <span class="cancion__n"><i class="bi bi-mic-fill"></i></span>
      <div class="cancion__t"><strong><?= e($ahora['titulo']) ?></strong><span>Mesa <?= e($ahora['mesa']) ?></span></div>
      <div class="d-flex gap-1">
        <form method="post" action="<?= url('cola/terminar') ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-field" type="submit">Terminó</button></form>
        <form method="post" action="<?= url('cola/saltar/' . (int) $ahora['id']) ?>" onsubmit="return confirm('¿Saltar esta canción?');"><?= csrf_field() ?>
          <button class="btn btn-sm btn-outline-field" type="submit">Saltar</button></form>
      </div>
    </div>
  <?php endif; ?>
</section>

<div class="row g-4">
  <div class="col-lg-8">
    <section class="panel mb-4">
      <div class="panel__head">
        <h2 class="panel__title">Siguientes</h2>
        <span class="panel__meta"><?= count($pendientes) ?> en la fila</span>
      </div>
      <?php if (!$pendientes): ?>
        <p class="empty mb-0">La fila está vacía. Anota la canción que pide una mesa en el formulario de la derecha.</p>
      <?php else: foreach ($pendientes as $i => $c): ?>
        <div class="cancion">
          <span class="cancion__n"><?= $i + 1 ?></span>
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

    <?php if ($recientes): ?>
    <section class="panel">
      <h2 class="panel__title mb-3">Ya cantaron</h2>
      <ul class="rows">
        <?php foreach ($recientes as $r): ?>
          <li><div><strong><?= e($r['titulo']) ?></strong><span>Mesa <?= e($r['mesa']) ?></span></div><time class="text-secondary"><?= $r['finalizada_en'] ? e(hora12($r['finalizada_en'])) : '' ?></time></li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php endif; ?>
  </div>

  <div class="col-lg-4">
    <section class="panel">
      <h2 class="panel__title mb-1">Anotar una canción</h2>
      <p class="text-secondary">La mesa te dice qué quiere cantar y tú la anotas aquí.</p>
      <?php if (!$mesas): ?>
        <p class="empty mb-0">No hay mesas abiertas todavía. Abre una en Salón y mesas.</p>
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
          <label class="form-label" for="titulo">Canción <small>(y artista, si quieres)</small></label>
          <input class="form-control mb-3" id="titulo" name="titulo" maxlength="150" required placeholder="Ej.: La Bikina - Luis Miguel" value="<?= old('titulo') ?>">
          <button class="btn btn-brass w-100" type="submit">Anotar en la fila</button>
        </form>
      <?php endif; ?>
    </section>
  </div>
</div>

<script>
  // Se actualiza sola cada 10 segundos (por si otro trabajador anota algo), salvo que estés escribiendo.
  setInterval(function () {
    var a = document.activeElement;
    if (a && (a.tagName === 'INPUT' || a.tagName === 'SELECT')) return;
    var campo = document.getElementById('titulo');
    if (campo && campo.value) return;
    location.reload();
  }, 10000);
</script>
