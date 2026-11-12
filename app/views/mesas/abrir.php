<header class="page-head page-head--actions">
  <div>
    <h1>Abrir mesa <?= e($m['numero']) ?></h1>
    <p>Para <?= (int) $m['capacidad'] ?> personas. Al abrirla puedes cargarle pedidos y anotarle canciones de karaoke.</p>
  </div>
  <a class="btn btn-outline-field" href="<?= url('mesas') ?>"><i class="bi bi-arrow-left"></i> Volver</a>
</header>

<?php if ($reserva): ?>
  <div class="notice notice--warn">
    <i class="bi bi-calendar-check"></i>
    <span>Esta mesa tiene una reserva de <strong><?= e($reserva['cliente']) ?></strong> a las <?= e(hora12($reserva['fecha_hora'])) ?> (<?= (int) $reserva['personas'] ?> personas<?= (float) $reserva['adelanto'] > 0 ? ', adelanto ' . e(money($reserva['adelanto'])) : '' ?>). Al abrir la mesa, la reserva queda como cumplida.</span>
  </div>
<?php endif; ?>

<section class="panel" style="max-width:560px">
  <form method="post" action="<?= url('mesas/iniciar/' . (int) $m['id']) ?>" autocomplete="off">
    <?= csrf_field() ?>
    <?php if ($reserva): ?><input type="hidden" name="reserva_id" value="<?= (int) $reserva['id'] ?>"><?php endif; ?>
    <label class="form-label" for="cliente">Nombre o referencia <small>(opcional)</small></label>
    <input class="form-control mb-3" id="cliente" name="cliente" maxlength="100" placeholder="Ej.: Cumpleaños de Luis" value="<?= old('cliente', $reserva['cliente'] ?? '') ?>" autofocus>
    <div class="d-flex gap-2">
      <button class="btn btn-brass" type="submit">Abrir mesa</button>
      <a class="btn btn-outline-field" href="<?= url('mesas') ?>">Cancelar</a>
    </div>
  </form>
</section>
