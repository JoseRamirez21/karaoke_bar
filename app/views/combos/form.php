<?php
$editar = $modo === 'editar';
$old    = $GLOBALS['_old'] ?? [];
$oldComp = is_array($old['comp'] ?? null) ? $old['comp'] : null;
$enVenta = $editar ? (int) $c['estado'] === 1 : (empty($old) || isset($old['estado']));
?>
<header class="page-head page-head--actions">
  <div>
    <h1><?= $editar ? 'Editar combo' : 'Nuevo combo' ?></h1>
    <p>Elige los productos que incluye (pon la cantidad de cada uno) y el precio especial.</p>
  </div>
  <a class="btn btn-outline-field" href="<?= url('combos') ?>"><i class="bi bi-arrow-left"></i> Volver</a>
</header>

<form method="post" action="<?= url($editar ? 'combos/actualizar/' . (int) $c['id'] : 'combos/guardar') ?>" autocomplete="off">
  <?= csrf_field() ?>
  <div class="row g-4">
    <div class="col-lg-5">
      <section class="panel">
        <h2 class="panel__title mb-3">Datos</h2>
        <div class="mb-3">
          <label class="form-label" for="nombre">Nombre</label>
          <input class="form-control" id="nombre" name="nombre" maxlength="120" required value="<?= old('nombre', $c['nombre'] ?? '') ?>" placeholder="Ej.: Promo 6 cervezas">
        </div>
        <div class="mb-3">
          <label class="form-label" for="descripcion">Descripción <small>(opcional)</small></label>
          <input class="form-control" id="descripcion" name="descripcion" maxlength="255" value="<?= old('descripcion', $c['descripcion'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label" for="precio">Precio del combo (S/)</label>
          <input class="form-control" id="precio" name="precio" inputmode="decimal" required value="<?= old('precio', isset($c['precio']) ? number_format((float) $c['precio'], 2, '.', '') : '') ?>" placeholder="0.00">
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6"><label class="form-label" for="desde">Vigente desde <small>(opcional)</small></label>
            <input class="form-control" id="desde" name="desde" type="date" value="<?= old('desde', $c['vigente_desde'] ?? '') ?>"></div>
          <div class="col-6"><label class="form-label" for="hasta">Hasta <small>(opcional)</small></label>
            <input class="form-control" id="hasta" name="hasta" type="date" value="<?= old('hasta', $c['vigente_hasta'] ?? '') ?>"></div>
        </div>
        <div class="form-check form-switch mb-4">
          <input class="form-check-input" type="checkbox" role="switch" id="estado" name="estado" value="1"<?= $enVenta ? ' checked' : '' ?>>
          <label class="form-check-label" for="estado">Disponible para la venta</label>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-brass" type="submit"><?= $editar ? 'Guardar cambios' : 'Crear combo' ?></button>
          <a class="btn btn-outline-field" href="<?= url('combos') ?>">Cancelar</a>
        </div>
      </section>
    </div>

    <div class="col-lg-7">
      <div class="toolbar">
        <div class="search-field"><i class="bi bi-search"></i>
          <input type="search" class="form-control" placeholder="Buscar producto..." data-filter-text="tablaComp" aria-label="Buscar producto"></div>
      </div>
      <section class="panel panel--flush">
        <div class="table-responsive">
          <table class="table table-app align-middle mb-0" id="tablaComp">
            <thead><tr><th>Producto</th><th class="text-end">Precio</th><th class="text-end" style="width:120px">Cantidad</th></tr></thead>
            <tbody>
            <?php foreach ($productos as $p):
                $q = $oldComp !== null ? (int) ($oldComp[$p['id']] ?? 0) : (int) ($actuales[$p['id']] ?? 0); ?>
              <tr class="<?= $q > 0 ? 'is-picked' : '' ?>" data-search="<?= e(mb_strtolower($p['nombre'] . ' ' . $p['categoria'])) ?>">
                <td><strong><?= e($p['nombre']) ?></strong><small class="d-block text-secondary"><?= e($p['categoria']) ?></small></td>
                <td class="text-end"><?= e(money($p['precio'])) ?></td>
                <td><input type="number" class="form-control text-center" name="comp[<?= (int) $p['id'] ?>]" value="<?= $q ?>" min="0" max="99" inputmode="numeric" aria-label="Cantidad de <?= e($p['nombre']) ?> en el combo"></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if (!$productos): ?><p class="empty m-4">Primero crea productos.</p><?php endif; ?>
        <p class="empty m-4" id="sinResultados" hidden>Ningún producto coincide con la búsqueda.</p>
      </section>
    </div>
  </div>
</form>
