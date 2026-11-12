<header class="page-head">
  <h1>Configuración</h1>
  <p>Ajustes generales del local.</p>
</header>

<section class="panel" style="max-width:640px">
  <form method="post" action="<?= url('configuracion/guardar') ?>" autocomplete="off">
    <?= csrf_field() ?>
    <div class="mb-3">
      <label class="form-label" for="nombre_local">Nombre del local</label>
      <input class="form-control" id="nombre_local" name="nombre_local" maxlength="60" required value="<?= old('nombre_local', $nombre) ?>">
      <div class="form-hint mb-0 mt-1">Sale en el menú, en el ingreso al sistema y en los tickets.</div>
    </div>
    <div class="mb-4">
      <label class="form-label" for="max_canciones_mesa">Canciones por mesa en la fila de karaoke</label>
      <input class="form-control" id="max_canciones_mesa" name="max_canciones_mesa" type="number" min="1" max="50" required value="<?= old('max_canciones_mesa', (string) $maximo) ?>" style="max-width:140px">
      <div class="form-hint mb-0 mt-1">Cuántas canciones puede tener anotadas una mesa a la vez, para que todas las mesas canten.</div>
    </div>
    <button class="btn btn-brass" type="submit">Guardar</button>
  </form>
</section>
