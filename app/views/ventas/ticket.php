<?php
$estado = ['pagada' => ['brass', 'Por entregar'], 'entregada' => ['ok', 'Entregada'], 'anulada' => ['danger', 'Anulada']][$v['estado']] ?? ['plain', $v['estado']];
$local  = Config::get('nombre_local', 'Karaoke Bar');
$sesion = (int) $v['sesion_mesa_id'];
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
  <?php endif; ?>
</div>

<article class="ticket">
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