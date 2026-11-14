<?php

$max = 0.0;
foreach ($resumen['metodos'] as $m) $max = max($max, (float) $m['total']);
$esperado = (float) $turno['monto_inicial'] + $resumen['efectivo'];
?>
<p class="big-figure"><?= e(money($resumen['total'])) ?></p>
<p class="text-secondary mb-4"><?= (int) $resumen['n'] ?> venta<?= $resumen['n'] === 1 ? '' : 's' ?> cobrada<?= $resumen['n'] === 1 ? '' : 's' ?></p>

<ul class="bars mb-4">
  <?php foreach ($resumen['metodos'] as $m):
      $pct = $max > 0 ? round(((float) $m['total'] / $max) * 100) : 0; ?>
    <li>
      <div class="bars__row"><span><?= e($m['nombre']) ?> <small class="text-secondary">(<?= (int) $m['n'] ?>)</small></span><strong><?= e(money($m['total'])) ?></strong></div>
      <div class="bars__track"><div class="bars__fill" style="width: <?= $pct ?>%"></div></div>
    </li>
  <?php endforeach; ?>
</ul>

<ul class="rows">
  <li><div><strong>Fondo inicial</strong></div><strong><?= e(money($turno['monto_inicial'])) ?></strong></li>
  <li><div><strong>Cobrado en efectivo</strong></div><strong><?= e(money($resumen['efectivo'])) ?></strong></li>
  <li><div><strong>Efectivo que debería haber</strong></div><strong class="text-brass"><?= e(money($esperado)) ?></strong></li>
</ul>

<?php if ((int) $resumen['anuladas']['n'] > 0): ?>
  <p class="form-hint">Anuladas: <?= (int) $resumen['anuladas']['n'] ?> (<?= e(money($resumen['anuladas']['total'])) ?>).</p>
<?php endif; ?>
<?php if ($resumen['por_entregar'] > 0): ?>
  <p class="form-hint"><?= $resumen['por_entregar'] ?> pedido<?= $resumen['por_entregar'] === 1 ? '' : 's' ?> cobrado<?= $resumen['por_entregar'] === 1 ? '' : 's' ?> aún sin entregar.</p>
<?php endif; ?>
