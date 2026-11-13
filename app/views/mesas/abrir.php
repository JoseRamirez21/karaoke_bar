<header class="page-head page-head--actions">
  <div>
    <h1>Abrir mesa <?= e($m['numero']) ?></h1>
  </div>
  <a class="btn btn-outline-field" href="<?= url('mesas') ?>"><i class="bi bi-arrow-left"></i> Volver</a>
</header>

<section class="panel" style="max-width:560px">
  <form method="post" action="<?= url('mesas/iniciar/' . (int) $m['id']) ?>" autocomplete="off">
    <?= csrf_field() ?>
    <label class="form-label" for="cliente">Nombre o referencia <small>(opcional)</small></label>
    <input class="form-control mb-3" id="cliente" name="cliente" maxlength="100" value="<?= old('cliente') ?>" autofocus>
    <div class="d-flex gap-2">
      <button class="btn btn-brass" type="submit">Abrir mesa</button>
      <a class="btn btn-outline-field" href="<?= url('mesas') ?>">Cancelar</a>
    </div>
  </form>
</section>
