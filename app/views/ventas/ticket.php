<?php
$estado  = ['pagada' => ['brass', 'Por entregar'], 'entregada' => ['ok', 'Entregada'], 'anulada' => ['danger', 'Anulada']][$v['estado']] ?? ['plain', $v['estado']];
$local   = Config::get('nombre_local', 'Karaoke Bar');
$sesion  = (int) $v['sesion_mesa_id'];
$anulada = $v['estado'] === 'anulada';
?>
<div class="no-print">
  <header class="page-head page-head--actions">
    <div>
      <h1>Ticket <?= e($v['codigo']) ?> <span class="badge-soft badge-soft--<?= $estado[0] ?> align-middle fs-6"><?= e($estado[1]) ?></span></h1>
      <p><?= $sesion ? 'Cargado a la mesa ' . e($v['mesa']) . '.' : 'Venta en el mostrador.' ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a class="btn btn-outline-field" href="<?= url($sesion ? 'mesas/cuenta/' . $sesion : 'mesas') ?>"><i class="bi bi-arrow-left"></i> <?= $sesion ? 'Cuenta de la mesa' : 'Salón' ?></a>
      <button class="btn btn-outline-field" type="button" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
      <a class="btn btn-brass" href="<?= url('ventas' . ($sesion ? '?sesion=' . $sesion : '')) ?>"><i class="bi bi-plus-lg"></i> Nueva venta</a>
    </div>
  </header>

  <?php if ($v['estado'] === 'pagada'): ?>
    <div class="notice notice--warn">
      <i class="bi bi-box-seam"></i>
      <span>Este pedido ya está cobrado. Entrégalo al cliente y márcalo como entregado.</span>
      <form method="post" action="<?= url('ventas/entregar/' . (int) $v['id']) ?>">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-brass" type="submit">Marcar entregado</button>
      </form>
    </div>

    <?php if ($razonNoAnular === null): ?>
      <section class="panel mb-4" id="anular" style="max-width:640px">
        <h2 class="panel__title mb-1">Anular este pedido</h2>
        <p class="text-secondary">Úsalo si te equivocaste al cobrar y el pedido aún no se entregó. Se devuelve el stock y deja de contar en la caja.</p>
        <form method="post" action="<?= url('ventas/anular/' . (int) $v['id']) ?>" autocomplete="off"
              data-confirm="¿Anular el pedido <?= e($v['codigo']) ?>?"
              data-confirm-text="Se devolverá el stock y se quitará de la caja. No se puede deshacer."
              data-confirm-button="Sí, anular">
          <?= csrf_field() ?>
          <label class="form-label" for="motivo">Motivo <small>(obligatorio)</small></label>
          <div class="input-group">
            <input class="form-control" id="motivo" name="motivo" maxlength="200" minlength="3" required placeholder="Ej.: me equivoqué de producto">
            <button class="btn btn-outline-brass" type="submit">Anular pedido</button>
          </div>
        </form>
      </section>
    <?php else: ?>
      <p class="form-hint"><?= e($razonNoAnular) ?></p>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($anulada): ?>
    <div class="notice notice--warn">
      <i class="bi bi-x-octagon"></i>
      <span>Pedido anulado por <?= e($v['anulada_por_nombre']) ?> el <?= e(fecha_corta($v['anulada_en'])) ?>. Motivo: <?= e($v['motivo_anulacion']) ?></span>
    </div>
  <?php endif; ?>
</div>

<article class="ticket<?= $anulada ? ' ticket--anulada' : '' ?>">
  <?php if ($anulada): ?><p class="ticket__sello">ANULADO</p><?php endif; ?>
  <h2><?= e($local) ?></h2>
  <p class="ticket__sub">Ticket <?= e($v['codigo']) ?></p>
  <div class="ticket__line"><span>Fecha</span><span><?= e(fecha_corta($v['created_at'])) ?></span></div>
  <div class="ticket__line"><span>Lugar</span><span><?= $sesion ? 'Mesa ' . e($v['mesa']) : 'Mostrador' ?></span></div>
  <div class="ticket__line"><span>Atendió</span><span><?= e($v['cajero']) ?></span></div>
  <hr>
  <?php foreach ($items as $it): ?>
    <div class="ticket__item">
      <div class="ticket__line"><span><?= e($it['descripcion']) ?></span><span><?= e(money($it['subtotal'])) ?></span></div>
      <small><?= (int) $it['cantidad'] ?> x <?= e(money($it['precio_unitario'])) ?></small>
    </div>
  <?php endforeach; ?>
  <hr>
  <div class="ticket__line ticket__total"><span>TOTAL</span><span><?= e(money($v['total'])) ?></span></div>
  <div class="ticket__line"><span>Pagó con</span><span><?= e($v['metodo']) ?></span></div>
  <hr>
  <p class="ticket__sub mb-0">¡Gracias por tu visita!</p>
</article>
