<?php
$cerrado = $t['estado'] === 'cerrado';
$dif     = (float) $t['diferencia'];
$turno   = $t;   // la parte común (_resumen.php) espera la variable $turno
?>
<header class="page-head page-head--actions">
  <div>
    <h1>Turno #<?= (int) $t['id'] ?></h1>
    <p><?= e($t['cajero']) ?>, desde el <?= e(fecha_corta($t['abierto_en'])) ?><?= $cerrado ? ' hasta el ' . e(fecha_corta($t['cerrado_en'])) : ' (sigue abierto)' ?></p>
  </div>
  <a class="btn btn-outline-field" href="<?= url('caja') ?>"><i class="bi bi-arrow-left"></i> Volver</a>
</header>

<div class="row g-4">
  <div class="col-lg-7">
    <section class="panel">
      <h2 class="panel__title mb-3">Lo cobrado</h2>
      <?php include APP_PATH . '/views/caja/_resumen.php'; ?>
    </section>
  </div>
  <div class="col-lg-5">
    <section class="panel">
      <h2 class="panel__title mb-3">Cierre de caja</h2>
      <?php if (!$cerrado): ?>
        <p class="empty mb-0">Este turno todavía está abierto.</p>
      <?php else: ?>
        <ul class="rows">
          <li><div><strong>Esperado</strong></div><strong><?= e(money($t['efectivo_esperado'])) ?></strong></li>
          <li><div><strong>Contado</strong></div><strong><?= e(money($t['efectivo_contado'])) ?></strong></li>
          <li><div><strong>Resultado</strong></div>
            <?php if (abs($dif) < 0.005): ?><span class="badge-soft badge-soft--ok">La caja cuadra</span>
            <?php else: ?><span class="badge-soft badge-soft--<?= $dif < 0 ? 'danger' : 'brass' ?>"><?= $dif < 0 ? 'Faltan ' : 'Sobran ' ?><?= e(money(abs($dif))) ?></span><?php endif; ?>
          </li>
        </ul>
        <?php if ($t['observacion']): ?><p class="form-hint">Observación: <?= e($t['observacion']) ?></p><?php endif; ?>
      <?php endif; ?>
    </section>
  </div>
</div>
