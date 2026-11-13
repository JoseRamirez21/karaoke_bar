<header class="page-head page-head--actions">
  <div>
    <h1>Categorías</h1>
  </div>
  <a class="btn btn-outline-field" href="<?= url('productos') ?>"><i class="bi bi-cup-straw"></i> Ir a productos</a>
</header>

<section class="panel mb-4">
  <h2 class="panel__title mb-3">Nueva categoría</h2>
  <form method="post" action="<?= url('categorias/guardar') ?>" class="row g-2 align-items-end" autocomplete="off">
    <?= csrf_field() ?>
    <div class="col-sm">
      <label class="form-label" for="nombre">Nombre</label>
      <input class="form-control" id="nombre" name="nombre" maxlength="80" required value="<?= old('nombre') ?>">
    </div>
    <div class="col-sm-auto"><button class="btn btn-brass w-100" type="submit"><i class="bi bi-plus-lg"></i> Agregar</button></div>
  </form>
</section>

<section class="panel panel--flush">
  <div class="table-responsive">
    <table class="table table-app align-middle mb-0">
      <thead><tr><th>Nombre</th><th>Productos</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
      <tbody>
      <?php foreach ($categorias as $c): ?>
        <tr class="<?= (int) $c['estado'] ? '' : 'is-off' ?>">
          <td style="min-width:220px">
            <form method="post" action="<?= url('categorias/actualizar/' . (int) $c['id']) ?>" class="d-flex gap-2" autocomplete="off">
              <?= csrf_field() ?>
              <input class="form-control form-control-sm" name="nombre" maxlength="80" value="<?= e($c['nombre']) ?>" aria-label="Nombre de la categoría" required>
              <button class="btn btn-sm btn-outline-field" type="submit">Guardar</button>
            </form>
          </td>
          <td><?= (int) $c['productos'] ?> <small class="text-secondary">(<?= (int) $c['activos'] ?> activos)</small></td>
          <td><span class="badge-soft badge-soft--<?= (int) $c['estado'] ? 'ok' : 'off' ?>"><?= (int) $c['estado'] ? 'Activa' : 'Desactivada' ?></span></td>
          <td class="text-end">
            <form method="post" action="<?= url('categorias/estado/' . (int) $c['id']) ?>" class="d-inline"
                  data-confirm="<?= (int) $c['estado'] ? '¿Desactivar ' . e($c['nombre']) . '?' : '¿Activar ' . e($c['nombre']) . '?' ?>"
                  data-confirm-button="Sí, continuar">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-field" type="submit"><?= (int) $c['estado'] ? 'Desactivar' : 'Activar' ?></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
