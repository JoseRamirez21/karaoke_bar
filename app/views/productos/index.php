<header class="page-head page-head--actions">
  <div>
    <h1>Productos</h1>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a class="btn btn-outline-field" href="<?= url('categorias') ?>"><i class="bi bi-bookmarks"></i> Categorías</a>
    <a class="btn btn-brass" href="<?= url('productos/crear') ?>"><i class="bi bi-plus-lg"></i> Nuevo producto</a>
  </div>
</header>

<div class="toolbar">
  <div class="search-field">
    <i class="bi bi-search"></i>
    <input type="search" class="form-control" placeholder="Buscar producto..." data-filter-text="tablaProductos" aria-label="Buscar producto">
  </div>
  <select class="form-select" data-filter-attr="tablaProductos" data-attr="cat" aria-label="Filtrar por categoría">
    <option value="">Todas las categorías</option>
    <?php foreach ($categorias as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['nombre']) ?></option><?php endforeach; ?>
  </select>
  <select class="form-select" data-filter-attr="tablaProductos" data-attr="stock" aria-label="Filtrar por stock">
    <option value="">Todo el stock</option>
    <option value="bajo">Solo stock bajo o agotado</option>
  </select>
</div>

<section class="panel panel--flush">
  <div class="table-responsive">
    <table class="table table-app align-middle mb-0" id="tablaProductos">
      <thead><tr><th>Producto</th><th>Categoría</th><th class="text-end">Precio</th><th class="text-end">Stock</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
      <tbody>
      <?php foreach ($productos as $p):
          $agotado = (int) $p['stock'] <= 0;
          $bajo    = !$agotado && (int) $p['stock'] <= (int) $p['stock_minimo']; ?>
        <tr class="<?= (int) $p['estado'] ? '' : 'is-off' ?>"
            data-search="<?= e(mb_strtolower($p['nombre'] . ' ' . $p['categoria'])) ?>"
            data-cat="<?= (int) $p['categoria_id'] ?>" data-stock="<?= ($agotado || $bajo) ? 'bajo' : 'ok' ?>">
          <td><strong><?= e($p['nombre']) ?></strong></td>
          <td class="text-secondary"><?= e($p['categoria']) ?></td>
          <td class="text-end"><?= e(money($p['precio'])) ?></td>
          <td class="text-end">
            <?php if ($agotado): ?><span class="badge-soft badge-soft--danger">Agotado</span>
            <?php elseif ($bajo): ?><span class="badge-soft badge-soft--brass"><?= (int) $p['stock'] ?> (bajo)</span>
            <?php else: ?><?= (int) $p['stock'] ?><?php endif; ?>
          </td>
          <td><span class="badge-soft badge-soft--<?= (int) $p['estado'] ? 'ok' : 'off' ?>"><?= (int) $p['estado'] ? 'En venta' : 'Desactivado' ?></span></td>
          <td class="text-end actions">
            <a class="btn btn-sm btn-outline-field" href="<?= url('productos/editar/' . (int) $p['id']) ?>"><i class="bi bi-pencil"></i> Editar</a>
            <form method="post" action="<?= url('productos/estado/' . (int) $p['id']) ?>" class="d-inline"
                  data-confirm="<?= (int) $p['estado'] ? '¿Quitar ' . e($p['nombre']) . ' de la venta?' : '¿Volver a vender ' . e($p['nombre']) . '?' ?>"
                  data-confirm-button="Sí, continuar">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-field" type="submit" title="<?= (int) $p['estado'] ? 'Desactivar' : 'Activar' ?>"><i class="bi bi-<?= (int) $p['estado'] ? 'eye-slash' : 'eye' ?>"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (!$productos): ?><p class="empty m-4">Aún no hay productos.</p><?php endif; ?>
  <p class="empty m-4" id="sinResultados" hidden>Ningún producto coincide con la búsqueda.</p>
</section>
