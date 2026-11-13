<header class="page-head page-head--actions">
  <div>
    <h1>Combos</h1>
  </div>
  <a class="btn btn-brass" href="<?= url('combos/crear') ?>"><i class="bi bi-plus-lg"></i> Nuevo combo</a>
</header>

<section class="panel panel--flush">
  <div class="table-responsive">
    <table class="table table-app align-middle mb-0">
      <thead><tr><th>Combo</th><th>Incluye</th><th class="text-end">Precio</th><th class="text-end">Ahorro</th><th>Vigencia</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
      <tbody>
      <?php foreach ($combos as $c):
          $ahorro = (float) $c['precio_normal'] - (float) $c['precio'];
          $hoy = date('Y-m-d');
          $vencido = $c['vigente_hasta'] && $c['vigente_hasta'] < $hoy;
          $futuro  = $c['vigente_desde'] && $c['vigente_desde'] > $hoy; ?>
        <tr class="<?= (int) $c['estado'] ? '' : 'is-off' ?>">
          <td><strong><?= e($c['nombre']) ?></strong><?php if ($c['descripcion']): ?><small class="d-block text-secondary"><?= e($c['descripcion']) ?></small><?php endif; ?></td>
          <td class="text-secondary"><?= e($resumenes[$c['id']] ?? '-') ?></td>
          <td class="text-end"><strong><?= e(money($c['precio'])) ?></strong></td>
          <td class="text-end"><?= $ahorro > 0.004 ? '<span class="badge-soft badge-soft--ok">' . e(money($ahorro)) . '</span>' : '-' ?></td>
          <td class="text-secondary">
            <?php if (!$c['vigente_desde'] && !$c['vigente_hasta']): ?>Siempre
            <?php else: ?><?= $c['vigente_desde'] ? 'Desde ' . e(date('d/m/Y', strtotime($c['vigente_desde']))) : '' ?> <?= $c['vigente_hasta'] ? 'hasta ' . e(date('d/m/Y', strtotime($c['vigente_hasta']))) : '' ?><?php endif; ?>
          </td>
          <td>
            <?php if (!(int) $c['estado']): ?><span class="badge-soft badge-soft--off">Desactivado</span>
            <?php elseif ($vencido): ?><span class="badge-soft badge-soft--danger">Vencido</span>
            <?php elseif ($futuro): ?><span class="badge-soft badge-soft--brass">Aún no empieza</span>
            <?php else: ?><span class="badge-soft badge-soft--ok">En venta</span><?php endif; ?>
          </td>
          <td class="text-end actions">
            <a class="btn btn-sm btn-outline-field" href="<?= url('combos/editar/' . (int) $c['id']) ?>"><i class="bi bi-pencil"></i> Editar</a>
            <form method="post" action="<?= url('combos/estado/' . (int) $c['id']) ?>" class="d-inline"
                  data-confirm="<?= (int) $c['estado'] ? '¿Desactivar ' . e($c['nombre']) . '?' : '¿Activar ' . e($c['nombre']) . '?' ?>" data-confirm-button="Sí, continuar">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-field" type="submit" title="<?= (int) $c['estado'] ? 'Desactivar' : 'Activar' ?>"><i class="bi bi-<?= (int) $c['estado'] ? 'eye-slash' : 'eye' ?>"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (!$combos): ?><p class="empty m-4">Aún no hay combos.</p><?php endif; ?>
</section>
