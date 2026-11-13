<header class="page-head">
  <h1>Configuración</h1>
</header>

<section class="panel" style="max-width:640px">
  <form method="post" action="<?= url('configuracion/guardar') ?>" autocomplete="off">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label" for="nombre_local">Nombre del local</label>
      <input class="form-control" id="nombre_local" name="nombre_local" maxlength="60" required value="<?= old('nombre_local', $nombre) ?>">
    </div>
    <button class="btn btn-brass" type="submit">Guardar</button>
  </form>
</section>
