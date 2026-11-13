<?php
$un_dia   = $desde === $hasta;
$maxDia   = 0.0; foreach ($dias as $d) $maxDia = max($maxDia, (float) $d['total']);
$maxMet   = 0.0; foreach ($metodos as $m) $maxMet = max($maxMet, (float) $m['total']);
$fmt = fn(string $f) => date('d/m/Y', strtotime($f));
$nomDia = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
?>
<header class="page-head page-head--actions">
  <div>
    <h1>Reportes</h1>
    <p><?= $un_dia ? e($fmt($desde)) : e($fmt($desde)) . ' al ' . e($fmt($hasta)) ?>.</p>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a class="btn btn-outline-field" href="<?= url('reportes/exportar?desde=' . $desde . '&hasta=' . $hasta) ?>"><i class="bi bi-file-earmark-spreadsheet"></i> Descargar Excel (CSV)</a>
    <button class="btn btn-outline-field" type="button" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
  </div>
</header>

<div class="toolbar">
  <form method="get" action="<?= url('reportes') ?>" class="d-flex gap-2 flex-wrap align-items-end">
    <div><label class="form-label mb-1" for="desde">Desde</label><input class="form-control" type="date" id="desde" name="desde" value="<?= e($desde) ?>" required></div>
    <div><label class="form-label mb-1" for="hasta">Hasta</label><input class="form-control" type="date" id="hasta" name="hasta" value="<?= e($hasta) ?>" required></div>
    <button class="btn btn-brass" type="submit">Ver</button>
  </form>
  <div class="d-flex gap-2 flex-wrap align-items-end">
    <?php foreach ($atajos as $txt => [$a, $b]): ?>
      <a class="btn btn-sm <?= ($a === $desde && $b === $hasta) ? 'btn-brass' : 'btn-outline-field' ?>" href="<?= url('reportes?desde=' . $a . '&hasta=' . $b) ?>"><?= e($txt) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg"><div class="mini"><small>Total vendido</small><strong class="text-brass"><?= e(money($resumen['total'])) ?></strong></div></div>
  <div class="col-6 col-lg"><div class="mini"><small>Ventas</small><strong><?= (int) $resumen['n'] ?></strong></div></div>
  <div class="col-6 col-lg"><div class="mini"><small>Ticket promedio</small><strong><?= e(money($resumen['ticket'])) ?></strong></div></div>
  <div class="col-6 col-lg"><div class="mini"><small>Anuladas</small><strong><?= (int) $resumen['anuladas_n'] ?></strong><small><?= e(money($resumen['anuladas_total'])) ?></small></div></div>
</div>

<div class="row g-4 mb-4">
  <?php if (!$un_dia): ?>
  <div class="col-lg-7">
    <section class="panel h-100">
      <h2 class="panel__title mb-3">Ventas por día</h2>
      <?php if (!$dias): ?><p class="empty mb-0">No hay ventas en estas fechas.</p>
      <?php else: ?>
        <ul class="bars">
          <?php foreach ($dias as $d): $pct = $maxDia > 0 ? round(((float) $d['total'] / $maxDia) * 100) : 0; ?>
            <li>
              <div class="bars__row"><span><?= e(ucfirst($nomDia[(int) date('w', strtotime($d['dia']))])) ?> <?= e($fmt($d['dia'])) ?> <small class="text-secondary">(<?= (int) $d['n'] ?> ventas)</small></span><strong><?= e(money($d['total'])) ?></strong></div>
              <div class="bars__track"><div class="bars__fill" style="width: <?= $pct ?>%"></div></div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>
  <?php endif; ?>

  <div class="col-lg-<?= $un_dia ? '6' : '5' ?>">
    <section class="panel h-100">
      <h2 class="panel__title mb-3">Por método de pago</h2>
      <ul class="bars">
        <?php foreach ($metodos as $m): $pct = $maxMet > 0 ? round(((float) $m['total'] / $maxMet) * 100) : 0; ?>
          <li>
            <div class="bars__row"><span><?= e($m['nombre']) ?> <small class="text-secondary">(<?= (int) $m['n'] ?>)</small></span><strong><?= e(money($m['total'])) ?></strong></div>
            <div class="bars__track"><div class="bars__fill" style="width: <?= $pct ?>%"></div></div>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  </div>

  <?php if ($un_dia): ?>
  <div class="col-lg-6">
    <section class="panel h-100">
      <h2 class="panel__title mb-3">Ventas por cajero</h2>
      <?php if (!$cajeros): ?><p class="empty mb-0">No hay ventas en estas fechas.</p><?php else: ?>
        <ul class="rows"><?php foreach ($cajeros as $c): ?>
          <li><div><strong><?= e($c['nombre']) ?></strong><span><?= (int) $c['n'] ?> ventas</span></div><strong><?= e(money($c['total'])) ?></strong></li>
        <?php endforeach; ?></ul>
      <?php endif; ?>
    </section>
  </div>
  <?php endif; ?>
</div>

<div class="row g-4 mb-4">
  <div class="col-12">
    <section class="panel h-100">
      <h2 class="panel__title mb-3">Lo más vendido</h2>
      <?php if (!$top): ?><p class="empty mb-0">No hay ventas en estas fechas.</p>
      <?php else: ?>
        <ul class="rows">
          <?php foreach ($top as $i => $p): ?>
            <li>
              <div><strong><?= ($i + 1) ?>. <?= e($p['descripcion']) ?><?= (int) $p['es_combo'] ? ' <span class="badge-soft badge-soft--brass">Combo</span>' : '' ?></strong><span><?= (int) $p['cantidad'] ?> unidades</span></div>
              <strong><?= e(money($p['total'])) ?></strong>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>

</div>

<?php if (!$un_dia && $cajeros): ?>
<section class="panel mb-4">
  <h2 class="panel__title mb-3">Ventas por cajero</h2>
  <ul class="rows"><?php foreach ($cajeros as $c): ?>
    <li><div><strong><?= e($c['nombre']) ?></strong><span><?= (int) $c['n'] ?> ventas</span></div><strong><?= e(money($c['total'])) ?></strong></li>
  <?php endforeach; ?></ul>
</section>
<?php endif; ?>

<section class="panel panel--flush">
  <div class="panel__head px-4 pt-3 mb-0">
    <h2 class="panel__title">Cierres de caja</h2>
    <span class="panel__meta"><?= $falta > 0.004 ? 'Faltaron ' . e(money($falta)) : 'Sin faltantes' ?><?= $sobra > 0.004 ? ', sobraron ' . e(money($sobra)) : '' ?></span>
  </div>
  <div class="table-responsive">
    <table class="table table-app align-middle mb-0">
      <thead><tr><th>Apertura</th><th>Cajero</th><th>Estado</th><th class="text-end">Esperado</th><th class="text-end">Contado</th><th class="text-end">Diferencia</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($turnos as $t): $cer = $t['estado'] === 'cerrado'; $dif = (float) $t['diferencia']; ?>
        <tr>
          <td><?= e(fecha_corta($t['abierto_en'])) ?></td>
          <td><?= e($t['cajero']) ?></td>
          <td><span class="badge-soft badge-soft--<?= $cer ? 'plain' : 'ok' ?>"><?= $cer ? 'Cerrado' : 'Abierto' ?></span></td>
          <td class="text-end"><?= $cer ? e(money($t['efectivo_esperado'])) : '-' ?></td>
          <td class="text-end"><?= $cer ? e(money($t['efectivo_contado'])) : '-' ?></td>
          <td class="text-end">
            <?php if (!$cer): ?>-<?php elseif (abs($dif) < 0.005): ?><span class="badge-soft badge-soft--ok">Cuadra</span>
            <?php else: ?><span class="badge-soft badge-soft--<?= $dif < 0 ? 'danger' : 'brass' ?>"><?= $dif < 0 ? 'Faltan ' : 'Sobran ' ?><?= e(money(abs($dif))) ?></span><?php endif; ?>
          </td>
          <td class="text-end"><a class="btn btn-sm btn-outline-field" href="<?= url('caja/detalle/' . (int) $t['id']) ?>">Ver</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (!$turnos): ?><p class="empty m-4">No hubo turnos de caja en estas fechas.</p><?php endif; ?>
</section>
