<?php
$editar  = $modo === 'editar';
$accion  = $editar ? 'productos/actualizar/' . (int) $p['id'] : 'productos/guardar';
$catSel  = $GLOBALS['_old']['categoria_id'] ?? ($p['categoria_id'] ?? '');
$etiq    = ['entrada' => 'Entrada', 'salida' => 'Salida', 'ajuste' => 'Ajuste', 'venta' => 'Venta', 'anulacion' => 'Anulación'];
$signo   = ['entrada' => '+', 'anulacion' => '+', 'salida' => '−', 'venta' => '−', 'ajuste' => '±'];
$enVenta = $editar ? (int) $p['estado'] === 1 : (empty($GLOBALS['_old']) || isset($GLOBALS['_old']['estado']));
?>
<header class="page-head page-head--actions">
  <div>
    <h1><?= $editar ? 'Editar producto' : 'Nuevo producto' ?></h1>
    <?php if ($editar): ?><p><?= e($p['nombre']) ?>, stock actual: <?= (int) $p['stock'] ?></p><?php endif; ?>
  </div>
  <a class="btn btn-outline-field" href="<?= url('productos') ?>"><i class="bi bi-arrow-left"></i> Volver</a>
</header>

<?php if (!$categorias): ?>
  <div class="notice notice--warn"><i class="bi bi-info-circle"></i><span>Primero crea al menos una categoría.</span><a class="btn btn-sm btn-brass" href="<?= url('categorias') ?>">Ir a categorías</a></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-7">
    <section class="panel">
      <h2 class="panel__title mb-3">Datos</h2>
      <form method="post" action="<?= url($accion) ?>" autocomplete="off">
        <?= csrf_field() ?>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label" for="nombre">Nombre</label>
            <input class="form-control" id="nombre" name="nombre" maxlength="120" required value="<?= old('nombre', $p['nombre'] ?? '') ?>">
          </div>
          <div class="col-md-7">
            <label class="form-label" for="categoria_id">Categoría</label>
            <select class="form-select" id="categoria_id" name="categoria_id" required>
              <option value="">Elige...</option>
              <?php foreach ($categorias as $c): ?><option value="<?= (int) $c['id'] ?>"<?= sel($catSel, $c['id']) ?>><?= e($c['nombre']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label" for="precio">Precio de venta (S/)</label>
            <input class="form-control" id="precio" name="precio" inputmode="decimal" required placeholder="0.00" value="<?= old('precio', isset($p['precio']) ? number_format((float) $p['precio'], 2, '.', '') : '') ?>">
          </div>
          <?php if (!$editar): ?>
            <div class="col-md-6">
              <label class="form-label" for="stock">Stock inicial</label>
              <input class="form-control" id="stock" name="stock" inputmode="numeric" value="<?= old('stock', '0') ?>">
            </div>
          <?php endif; ?>
          <div class="col-md-6">
            <label class="form-label" for="stock_minimo">Avisar cuando queden</label>
            <input class="form-control" id="stock_minimo" name="stock_minimo" inputmode="numeric" value="<?= old('stock_minimo', (string) ($p['stock_minimo'] ?? '5')) ?>">
          </div>
          <div class="col-12">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" id="estado" name="estado" value="1"<?= $enVenta ? ' checked' : '' ?>>
              <label class="form-check-label" for="estado">Disponible para la venta</label>
            </div>
          </div>
        </div>
        <div class="mt-4 d-flex gap-2">
          <button class="btn btn-brass" type="submit"><?= $editar ? 'Guardar cambios' : 'Crear producto' ?></button>
          <a class="btn btn-outline-field" href="<?= url('productos') ?>">Cancelar</a>
        </div>
      </form>
    </section>
  </div>

  <?php if ($editar): ?>
  <div class="col-lg-5">
    <section class="panel mb-4" id="stock">
      <h2 class="panel__title mb-1">Mover stock</h2>
      <p class="text-secondary">Hay <strong class="text-body"><?= (int) $p['stock'] ?></strong> en este momento.</p>
      <form method="post" action="<?= url('productos/stock/' . (int) $p['id']) ?>" autocomplete="off">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label" for="tipo">¿Qué pasó?</label>
          <select class="form-select" id="tipo" name="tipo" required>
            <option value="entrada">Llegó mercadería (suma)</option>
            <option value="salida">Salió o se perdió (resta)</option>
            <option value="ajuste">Contar de nuevo: fijar el stock en...</option>
          </select>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-4">
            <label class="form-label" for="cantidad">Cantidad</label>
            <input class="form-control" id="cantidad" name="cantidad" inputmode="numeric" required>
          </div>
          <div class="col-8">
            <label class="form-label" for="motivo">Motivo</label>
            <input class="form-control" id="motivo" name="motivo" maxlength="150">
          </div>
        </div>
        <button class="btn btn-outline-brass" type="submit">Registrar movimiento</button>
      </form>
    </section>

    <section class="panel">
      <h2 class="panel__title mb-3">Últimos movimientos</h2>
      <?php if (!$movimientos): ?>
        <p class="empty mb-0">Todavía no hay movimientos de stock.</p>
      <?php else: ?>
        <ul class="rows">
          <?php foreach ($movimientos as $m): ?>
            <li>
              <div>
                <strong><?= e($etiq[$m['tipo']] ?? $m['tipo']) ?> <?= $signo[$m['tipo']] ?? '' ?><?= (int) $m['cantidad'] ?>, queda <?= (int) $m['stock_resultante'] ?></strong>
                <span><?= e($m['motivo'] ?: 'Sin motivo') ?>. <?= e($m['usuario']) ?></span>
              </div>
              <time class="text-secondary"><?= e(date('d/m H:i', strtotime($m['created_at']))) ?></time>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>
  <?php endif; ?>
</div>
