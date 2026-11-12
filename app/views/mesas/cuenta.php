<?php
$abierta     = $s['estado'] === 'abierta';
$estadoVenta = ['pagada' => ['brass', 'Por entregar'], 'entregada' => ['ok', 'Entregada'], 'anulada' => ['danger', 'Anulada']];
$pedidas     = array_sum($canciones);
$enCola      = ($canciones['pendiente'] ?? 0) + ($canciones['reproduciendo'] ?? 0);
?>
<header class="page-head page-head--actions">
  <div>
    <h1>Mesa <?= e($s['mesa']) ?> <span class="badge-soft badge-soft--<?= $abierta ? 'rose' : 'plain' ?> align-middle fs-6"><?= $abierta ? 'Abierta' : 'Cerrada' ?></span></h1>
    <p>
      Abierta a las <?= e(hora12($s['abierta_en'])) ?> por <?= e($s['abierta_por_nombre']) ?><?= $s['cliente'] ? ', ' . e($s['cliente']) : '' ?>.
      <?php if (!$abierta): ?>Cerrada el <?= e(fecha_corta($s['cerrada_en'])) ?> por <?= e($s['cerrada_por_nombre']) ?>.<?php endif; ?>
    </p>
  </div>
  <a class="btn btn-outline-field" href="<?= url('mesas') ?>"><i class="bi bi-arrow-left"></i> Salón</a>
</header>

<div class="row g-4">
  <div class="col-lg-7">
    <section class="panel h-100">
      <div class="panel__head">
        <h2 class="panel__title">Pedidos</h2>
        <?php if ($abierta): ?><a class="btn btn-sm btn-brass" href="<?= url('ventas?sesion=' . (int) $s['id']) ?>"><i class="bi bi-plus-lg"></i> Agregar pedido</a><?php endif; ?>
      </div>
      <p class="big-figure"><?= e(money($resumen['total'])) ?></p>
      <p class="text-secondary mb-4">Consumido en <?= (int) $resumen['n'] ?> pedido<?= $resumen['n'] === 1 ? '' : 's' ?></p>

      <?php if (!$ventas): ?>
        <p class="empty mb-0">Todavía no hay pedidos. Los pedidos que cobres para esta mesa aparecerán aquí.</p>
      <?php else: ?>
        <ul class="rows">
          <?php foreach ($ventas as $v): [$cls, $txt] = $estadoVenta[$v['estado']] ?? ['plain', $v['estado']]; ?>
            <li>
              <div>
                <a href="<?= url('ventas/ticket/' . (int) $v['id']) ?>"><strong><?= e($v['codigo']) ?></strong></a>
                <span><?= e(hora12($v['created_at'])) ?>, <?= e($v['metodo']) ?>, cobró <?= e($v['cajero']) ?></span>
              </div>
              <div class="text-end">
                <strong><?= e(money($v['total'])) ?></strong>
                <span class="badge-soft badge-soft--<?= $cls ?>"><?= e($txt) ?></span>
                <?php if ($v['estado'] === 'pagada'): ?>
                  <form method="post" action="<?= url('ventas/entregar/' . (int) $v['id']) ?>" class="mt-1">
                    <?= csrf_field() ?>
                    <input type="hidden" name="volver" value="mesas/cuenta/<?= (int) $s['id'] ?>">
                    <button class="btn btn-sm btn-outline-brass" type="submit">Marcar entregado</button>
                    <a class="btn btn-sm btn-outline-field" href="<?= url('ventas/ticket/' . (int) $v['id'] . '#anular') ?>">Anular</a>
                  </form>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>

  <div class="col-lg-5">
    <section class="panel mb-4">
      <h2 class="panel__title mb-3">Karaoke</h2>
      <ul class="rows">
        <li><div><strong>Canciones anotadas</strong></div><strong><?= (int) $pedidas ?></strong></li>
        <li><div><strong>En cola ahora</strong></div><strong><?= (int) $enCola ?></strong></li>
      </ul>
      <?php if ($abierta): ?><a class="btn btn-sm btn-outline-field mt-3" href="<?= url('cola?sesion=' . (int) $s['id']) ?>"><i class="bi bi-music-note-list"></i> Agregar canción</a><?php endif; ?>
    </section>

    <?php if ($abierta): ?>
    <section class="panel">
      <h2 class="panel__title mb-1">Cerrar mesa</h2>
      <p class="text-secondary">Hazlo cuando los clientes se van. Se libera la mesa y se quitan las canciones que aún no cantaron.</p>
      <?php if ($resumen['por_entregar'] > 0): ?>
        <div class="notice notice--warn"><i class="bi bi-exclamation-triangle"></i><span><?= (int) $resumen['por_entregar'] ?> pedido<?= $resumen['por_entregar'] === 1 ? '' : 's' ?> cobrado<?= $resumen['por_entregar'] === 1 ? '' : 's' ?> sin entregar. Entrégalo<?= $resumen['por_entregar'] === 1 ? '' : 's' ?> antes de cerrar.</span></div>
      <?php endif; ?>
      <form method="post" action="<?= url('mesas/cerrar/' . (int) $s['id']) ?>"
            data-confirm="¿Cerrar la mesa <?= e($s['mesa']) ?>?"
            data-confirm-text="Consumo total: <?= e(money($resumen['total'])) ?>. Se quitarán sus canciones pendientes."
            data-confirm-button="Sí, cerrar mesa">
        <?= csrf_field() ?>
        <button class="btn btn-brass" type="submit">Cerrar mesa</button>
      </form>
    </section>
    <?php endif; ?>
  </div>
</div>
