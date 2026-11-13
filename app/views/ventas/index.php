<?php
$old       = $GLOBALS['_old'] ?? [];
$oldItems  = is_array($old['items'] ?? null) ? $old['items'] : [];
$oldCombos = is_array($old['combos'] ?? null) ? $old['combos'] : [];
$selSesion = (int) ($old['sesion_id'] ?? $sesionPre);
$selMetodo = (int) ($old['metodo_id'] ?? 0);
?>
<header class="page-head">
  <h1>Vender</h1>
</header>

<?php if (!$turno): ?>
  <div class="notice notice--warn">
    <i class="bi bi-cash-stack"></i>
    <span>No tienes un turno de caja abierto.</span>
    <a class="btn btn-sm btn-brass" href="<?= url('caja') ?>">Abrir mi caja</a>
  </div>
<?php else: ?>
<form method="post" action="<?= url('ventas/guardar') ?>" id="formVenta" autocomplete="off">
  <?= csrf_field() ?>
  <input type="hidden" name="token" value="<?= e($token) ?>">

  <div class="row g-4">
    <!-- PRODUCTOS -->
    <div class="col-xl-8">
      <?php if ($combos): ?>
      <section class="panel panel--flush mb-3">
        <div class="panel__head px-4 pt-3 mb-0"><h2 class="panel__title">Combos</h2></div>
        <div class="table-responsive">
          <table class="table table-app align-middle mb-0" id="tablaCombos">
            <tbody>
            <?php foreach ($combos as $co):
                $agot = (int) $co['disponibles'] <= 0;
                $q = min((int) ($oldCombos[$co['id']] ?? 0), (int) $co['disponibles']); ?>
              <tr class="<?= $agot ? 'is-off' : '' ?>" data-id="<?= (int) $co['id'] ?>" data-nombre="<?= e($co['nombre']) ?>" data-precio="<?= e(number_format((float) $co['precio'], 2, '.', '')) ?>">
                <td><strong><?= e($co['nombre']) ?></strong> <span class="badge-soft badge-soft--brass">Combo</span><small class="d-block text-secondary"><?= e($co['incluye']) ?></small></td>
                <td class="text-end"><?= e(money($co['precio'])) ?></td>
                <td class="text-end"><?= $agot ? '<span class="badge-soft badge-soft--danger">Agotado</span>' : (int) $co['disponibles'] ?></td>
                <td>
                  <?php if (!$agot): ?>
                    <div class="qty">
                      <button type="button" class="btn btn-outline-field" data-qty="-1" aria-label="Quitar uno">−</button>
                      <input type="number" class="form-control" name="combos[<?= (int) $co['id'] ?>]" value="<?= $q ?>" min="0" max="<?= (int) $co['disponibles'] ?>" inputmode="numeric" aria-label="Cantidad de <?= e($co['nombre']) ?>">
                      <button type="button" class="btn btn-outline-field" data-qty="1" aria-label="Agregar uno">+</button>
                    </div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
      <?php endif; ?>

      <div class="toolbar">
        <div class="search-field">
          <i class="bi bi-search"></i>
          <input type="search" class="form-control" placeholder="Buscar producto..." data-filter-text="tablaVenta" aria-label="Buscar producto">
        </div>
        <select class="form-select" data-filter-attr="tablaVenta" data-attr="cat" aria-label="Filtrar por categoría">
          <option value="">Todas las categorías</option>
          <?php foreach ($categorias as $cid => $cn): ?><option value="<?= (int) $cid ?>"><?= e($cn) ?></option><?php endforeach; ?>
        </select>
      </div>

      <section class="panel panel--flush">
        <div class="table-responsive">
          <table class="table table-app align-middle mb-0" id="tablaVenta">
            <thead><tr><th>Producto</th><th class="text-end">Precio</th><th class="text-end">Stock</th><th class="text-end">Cantidad</th></tr></thead>
            <tbody>
            <?php foreach ($productos as $p):
                $agotado = (int) $p['stock'] <= 0;
                $q = min((int) ($oldItems[$p['id']] ?? 0), (int) $p['stock']); ?>
              <tr class="<?= $agotado ? 'is-off' : '' ?>" data-id="<?= (int) $p['id'] ?>"
                  data-nombre="<?= e($p['nombre']) ?>" data-precio="<?= e(number_format((float) $p['precio'], 2, '.', '')) ?>"
                  data-search="<?= e(mb_strtolower($p['nombre'] . ' ' . $p['categoria'])) ?>" data-cat="<?= (int) $p['categoria_id'] ?>">
                <td><strong><?= e($p['nombre']) ?></strong><small class="d-block text-secondary"><?= e($p['categoria']) ?></small></td>
                <td class="text-end"><?= e(money($p['precio'])) ?></td>
                <td class="text-end"><?= $agotado ? '<span class="badge-soft badge-soft--danger">Agotado</span>' : (int) $p['stock'] ?></td>
                <td>
                  <?php if (!$agotado): ?>
                    <div class="qty">
                      <button type="button" class="btn btn-outline-field" data-qty="-1" aria-label="Quitar uno">−</button>
                      <input type="number" class="form-control" name="items[<?= (int) $p['id'] ?>]" value="<?= $q ?>" min="0" max="<?= (int) $p['stock'] ?>" inputmode="numeric" aria-label="Cantidad de <?= e($p['nombre']) ?>">
                      <button type="button" class="btn btn-outline-field" data-qty="1" aria-label="Agregar uno">+</button>
                    </div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if (!$productos): ?><p class="empty m-4">No hay productos disponibles.</p><?php endif; ?>
        <p class="empty m-4" id="sinResultados" hidden>Ningún producto coincide con la búsqueda.</p>
      </section>
    </div>

    <!-- PEDIDO -->
    <div class="col-xl-4">
      <section class="panel pos-summary">
        <h2 class="panel__title mb-3">Pedido</h2>

        <p class="empty mb-3" id="resumenVacio">Pedido vacío.</p>
        <ul class="pos-lines" id="resumenLista"></ul>
        <div class="d-flex justify-content-between align-items-baseline mb-4">
          <span class="text-secondary">Total</span>
          <span class="big-figure mb-0" id="resumenTotal">S/ 0.00</span>
        </div>

        <label class="form-label" for="sesion_id">¿Para quién es?</label>
        <select class="form-select mb-3" id="sesion_id" name="sesion_id">
          <option value="0">Mostrador (sin mesa)</option>
          <?php foreach ($mesas as $m): ?>
            <option value="<?= (int) $m['sesion_id'] ?>"<?= sel($selSesion, $m['sesion_id']) ?>>Mesa <?= e($m['numero']) ?><?= $m['cliente'] ? ' (' . e($m['cliente']) . ')' : '' ?></option>
          <?php endforeach; ?>
        </select>

        <label class="form-label" for="metodo_id">¿Cómo paga?</label>
        <select class="form-select mb-3" id="metodo_id" name="metodo_id" required>
          <option value="">Elige...</option>
          <?php foreach ($metodos as $mp): ?>
            <option value="<?= (int) $mp['id'] ?>" data-efectivo="<?= (int) $mp['es_efectivo'] ?>"<?= sel($selMetodo, $mp['id']) ?>><?= e($mp['nombre']) ?></option>
          <?php endforeach; ?>
        </select>

        <div id="bloqueEfectivo" class="mb-3" hidden>
          <label class="form-label" for="recibido">Efectivo recibido <small>(opcional)</small></label>
          <input class="form-control" id="recibido" name="recibido" inputmode="decimal">
          <p class="vuelto" id="vuelto"></p>
        </div>

        <button type="submit" class="btn btn-brass btn-lg w-100" id="btnCobrar" disabled>Cobrar</button>
      </section>
    </div>
  </div>
</form>
<script src="<?= asset('assets/js/ventas.js') ?>"></script>
<?php endif; ?>
