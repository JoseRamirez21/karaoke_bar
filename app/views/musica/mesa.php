<?php $local = Config::get('nombre_local', 'Karaoke Bar'); ?>
<div id="musica" data-estado="<?= url('musica/estado/' . $token) ?>" data-quitar="<?= url('musica/quitar/' . $token) ?>"
     data-csrf="<?= e(Csrf::token()) ?>" data-abierta="<?= $abierta ? '1' : '0' ?>">

  <header class="cliente-head">
    <span class="brand-mark"><i class="bi bi-mic-fill"></i></span>
    <div>
      <h1>Mesa <?= e($mesa['numero']) ?></h1>
      <p><?= e($local) ?></p>
    </div>
  </header>

  <?php if (!$abierta): ?>
    <section class="panel">
      <h2 class="panel__title mb-2">Esta mesa aún no está abierta</h2>
      <p class="text-secondary mb-0">Pídele al personal que abra tu mesa y podrás elegir tu música desde aquí.</p>
    </section>
  <?php else: ?>
    <section class="panel mb-3 ahora" id="ahoraPanel">
      <p class="text-secondary mb-1">Ahora suena</p>
      <p class="ahora__t" id="ahoraTitulo"><?= $ahora ? e($ahora['titulo']) : 'Nada por ahora. ¡Anímate a cantar!' ?></p>
      <p class="text-secondary mb-0" id="ahoraMesa"><?= $ahora ? 'Mesa ' . e($ahora['mesa']) : '' ?></p>
    </section>

    <section class="panel mb-3">
      <h2 class="panel__title mb-1">Pide tu canción</h2>
      <p class="text-secondary">En YouTube busca tu canción (puedes buscar "karaoke"), toca <strong>Compartir</strong>, luego <strong>Copiar enlace</strong>, y pégalo aquí.</p>
      <form method="post" action="<?= url('musica/agregar/' . $token) ?>" autocomplete="off">
        <?= csrf_field() ?>
        <input class="form-control form-control-lg mb-3" name="enlace" inputmode="url" placeholder="Pega aquí el enlace de YouTube" required maxlength="300">
        <button class="btn btn-brass btn-lg w-100" type="submit">Agregar a la cola</button>
      </form>
    </section>

    <section class="panel">
      <div class="panel__head">
        <h2 class="panel__title">Tus canciones</h2>
        <span class="panel__meta" id="cupo"><?= count($mias) ?> de <?= (int) $max ?></span>
      </div>
      <div id="mias">
        <?php if (!$mias): ?>
          <p class="empty mb-0">Aún no pides nada. Pega un enlace arriba.</p>
        <?php else: foreach ($mias as $c): ?>
          <div class="cancion">
            <img src="<?= e($c['miniatura']) ?>" alt="" loading="lazy">
            <div class="cancion__t">
              <strong><?= e($c['titulo']) ?></strong>
              <span><?= $c['sonando'] ? 'Sonando ahora' : ($c['posicion'] === 1 ? 'Sigue tu canción' : 'Puesto ' . (int) $c['posicion'] . ' en la fila') ?></span>
            </div>
            <?php if (!$c['sonando']): ?>
              <form method="post" action="<?= url('musica/quitar/' . $token) ?>">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <button class="btn btn-sm btn-outline-field" type="submit" aria-label="Quitar">Quitar</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </section>
  <?php endif; ?>
</div>