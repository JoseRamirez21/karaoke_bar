<?php $esAdmin = Auth::is('admin'); ?>
<header class="page-head">
  <h1>Mi caja</h1>
  <p><?= $turno ? 'Turno abierto desde las ' . e(hora12($turno['abierto_en'])) . '.' : 'Abre tu turno para empezar a cobrar.' ?></p>
</header>

<?php if (!$turno): ?>
  <div class="row g-4 mb-4">
    <div class="col-lg-6">
      <section class="panel">
        <h2 class="panel__title mb-1">Abrir turno</h2>
        <p class="text-secondary">Escribe cuánto efectivo hay en la caja al empezar, para dar vuelto. Puede ser 0.</p>
        <form method="post" action="<?= url('caja/abrir') ?>" autocomplete="off">
          <?= csrf_field() ?>
          <label class="form-label" for="monto_inicial">Efectivo inicial (S/)</label>
          <input class="form-control mb-3" id="monto_inicial" name="monto_inicial" inputmode="decimal" required value="<?= old('monto_inicial', '0.00') ?>">
          <button class="btn btn-brass" type="submit">Abrir turno</button>
        </form>
      </section>
    </div>
  </div>
<?php else: ?>
  <div class="row g-4 mb-4">
    <div class="col-lg-7">
      <section class="panel h-100">
        <h2 class="panel__title mb-3">Lo cobrado en este turno</h2>
        <?php include APP_PATH . '/views/caja/_resumen.php'; ?>
      </section>
    </div>
    <div class="col-lg-5">
      <section class="panel h-100">
        <h2 class="panel__title mb-1">Cerrar turno</h2>
        <p class="text-secondary">Cuenta el efectivo que hay en la caja y escríbelo. El sistema lo compara con lo esperado.</p>
        <form method="post" action="<?= url('caja/cerrar') ?>" autocomplete="off"
              data-confirm="¿Cerrar tu turno?" data-confirm-text="Ya no podrás cobrar hasta abrir un turno nuevo." data-confirm-button="Sí, cerrar turno">
          <?= csrf_field() ?>
          <label class="form-label" for="efectivo_contado">Efectivo contado (S/)</label>
          <input class="form-control mb-3" id="efectivo_contado" name="efectivo_contado" inputmode="decimal" required value="<?= old('efectivo_contado') ?>">
          <label class="form-label" for="observacion">Observación <small>(obligatoria si hay diferencia)</small></label>
          <input class="form-control mb-3" id="observacion" name="observacion" maxlength="255" value="<?= old('observacion') ?>" placeholder="Ej.: vuelto mal dado en la mesa 4">
          <button class="btn btn-brass" type="submit">Cerrar turno</button>
        </form>
      </section>
    </div>
  </div>
<?php endif; ?>

<section class="panel panel--flush">
  <div class="panel__head px-4 pt-3 mb-0"><h2 class="panel__title"><?= $esAdmin ? 'Turnos recientes de todos' : 'Tus turnos recientes' ?></h2></div>
  <div class="table-responsive">
    <table class="table table-app align-middle mb-0">
      <thead>
        <tr><th>Apertura</th><?php if ($esAdmin): ?><th>Cajero</th><?php endif; ?><th>Estado</th><th class="text-end">Esperado</th><th class="text-end">Contado</th><th class="text-end">Diferencia</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($historial as $h):
          $cerrado = $h['estado'] === 'cerrado';
          $dif     = (float) $h['diferencia']; ?>
        <tr>
          <td><?= e(fecha_corta($h['abierto_en'])) ?></td>
          <?php if ($esAdmin): ?><td><?= e($h['cajero']) ?></td><?php endif; ?>
          <td><span class="badge-soft badge-soft--<?= $cerrado ? 'plain' : 'ok' ?>"><?= $cerrado ? 'Cerrado' : 'Abierto' ?></span></td>
          <td class="text-end"><?= $cerrado ? e(money($h['efectivo_esperado'])) : '-' ?></td>
          <td class="text-end"><?= $cerrado ? e(money($h['efectivo_contado'])) : '-' ?></td>
          <td class="text-end">
            <?php if (!$cerrado): ?>-
            <?php elseif (abs($dif) < 0.005): ?><span class="badge-soft badge-soft--ok">Cuadra</span>
            <?php else: ?><span class="badge-soft badge-soft--<?= $dif < 0 ? 'danger' : 'brass' ?>"><?= $dif < 0 ? 'Faltan ' : 'Sobran ' ?><?= e(money(abs($dif))) ?></span>
            <?php endif; ?>
          </td>
          <td class="text-end"><a class="btn btn-sm btn-outline-field" href="<?= url('caja/detalle/' . (int) $h['id']) ?>">Ver</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (!$historial): ?><p class="empty m-4">Todavía no hay turnos.</p><?php endif; ?>
</section>
