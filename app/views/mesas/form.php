<?php $editar = $modo === 'editar'; ?>
<header class="page-head page-head--actions">
  <div>
    <h1><?= $editar ? 'Editar mesa' : 'Nueva mesa' ?></h1>
    <p><?= $editar ? 'Mesa ' . e($m['numero']) : 'Cuántas personas caben y cómo la llamas en el salón.' ?></p>
  </div>
  <a class="btn btn-outline-field" href="<?= url('mesas') ?>"><i class="bi bi-arrow-left"></i> Volver</a>
</header>

<section class="panel" style="max-width:560px">
  <form method="post" action="<?= url($editar ? 'mesas/actualizar/' . (int) $m['id'] : 'mesas/guardar') ?>" autocomplete="off">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label" for="numero">Número o nombre</label>
        <input class="form-control" id="numero" name="numero" maxlength="20" required value="<?= old('numero', $m['numero'] ?? '') ?>">
        <div class="form-hint mb-0 mt-1">Por ejemplo 7 o VIP1.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="capacidad">Capacidad (personas)</label>
        <input class="form-control" id="capacidad" name="capacidad" type="number" min="1" max="30" required value="<?= old('capacidad', (string) ($m['capacidad'] ?? '4')) ?>">
      </div>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button class="btn btn-brass" type="submit"><?= $editar ? 'Guardar cambios' : 'Crear mesa' ?></button>
      <a class="btn btn-outline-field" href="<?= url('mesas') ?>">Cancelar</a>
    </div>
  </form>
</section>
