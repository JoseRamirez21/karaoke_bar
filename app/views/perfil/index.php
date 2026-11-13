<header class="page-head">
  <h1>Mi perfil</h1>
</header>

<div class="row g-4">
  <div class="col-lg-5">
    <section class="panel">
      <h2 class="panel__title">Tu cuenta</h2>
      <dl class="data-list">
        <div><dt>Nombre</dt><dd><?= e($perfil['nombre']) ?></dd></div>
        <div><dt>Usuario</dt><dd><?= e($perfil['usuario']) ?></dd></div>
        <div><dt>Rol</dt><dd><?= $perfil['rol'] === 'admin' ? 'Administrador' : 'Cajero' ?></dd></div>
        <div><dt>Último ingreso</dt><dd><?= $perfil['ultimo_login'] ? e(fecha_larga(strtotime($perfil['ultimo_login'])) . ', ' . hora12($perfil['ultimo_login'])) : 'Este es tu primer ingreso' ?></dd></div>
      </dl>
    </section>
  </div>

  <div class="col-lg-7">
    <section class="panel">
      <h2 class="panel__title">Cambiar contraseña</h2>
      <form method="post" action="<?= url('perfil/password') ?>" autocomplete="off">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label" for="actual">Contraseña actual</label>
          <input type="password" class="form-control" id="actual" name="actual" required>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="nueva">Contraseña nueva</label>
            <input type="password" class="form-control" id="nueva" name="nueva" minlength="8" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="repetir">Repite la nueva</label>
            <input type="password" class="form-control" id="repetir" name="repetir" minlength="8" required>
          </div>
        </div>
        <button class="btn btn-brass" type="submit">Guardar contraseña</button>
      </form>
    </section>
  </div>
</div>
