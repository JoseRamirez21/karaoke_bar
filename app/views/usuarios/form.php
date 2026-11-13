<?php
$editar   = $modo === 'editar';
$yo       = $editar && (int) $u['id'] === Auth::id();
$rolActual = $GLOBALS['_old']['rol_id'] ?? ($u['rol_id'] ?? '');
$accion   = $editar ? 'usuarios/actualizar/' . (int) $u['id'] : 'usuarios/guardar';
?>
<header class="page-head page-head--actions">
  <div>
    <h1><?= $editar ? 'Editar usuario' : 'Nuevo usuario' ?></h1>
  </div>
  <a class="btn btn-outline-field" href="<?= url('usuarios') ?>"><i class="bi bi-arrow-left"></i> Volver</a>
</header>

<div class="row g-4">
  <div class="col-lg-7">
    <section class="panel">
      <h2 class="panel__title mb-3">Datos</h2>
      <form method="post" action="<?= url($accion) ?>" autocomplete="off">
        <?= csrf_field() ?>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label" for="nombre">Nombre completo</label>
            <input class="form-control" id="nombre" name="nombre" maxlength="100" required value="<?= old('nombre', $u['nombre'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="usuario">Usuario para ingresar</label>
            <input class="form-control" id="usuario" name="usuario" maxlength="30" required autocapitalize="off" value="<?= old('usuario', $u['usuario'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="telefono">Teléfono (opcional)</label>
            <input class="form-control" id="telefono" name="telefono" maxlength="20" inputmode="tel" value="<?= old('telefono', $u['telefono'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="rol_id">Rol</label>
            <select class="form-select" id="rol_id" name="rol_id" required <?= $yo ? 'disabled' : '' ?>>
              <?php foreach ($roles as $r): ?>
                <option value="<?= (int) $r['id'] ?>"<?= sel($rolActual, $r['id']) ?>><?= $r['nombre'] === 'admin' ? 'Administrador' : 'Cajero' ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php if (!$editar): ?>
            <div class="col-md-6">
              <label class="form-label" for="password">Contraseña inicial</label>
              <div class="input-group">
                <input type="password" class="form-control" id="password" name="password" minlength="8" required autocomplete="new-password">
                <button class="btn btn-outline-field" type="button" data-toggle-password="#password" aria-label="Mostrar u ocultar"><i class="bi bi-eye"></i></button>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <div class="mt-4 d-flex gap-2">
          <button class="btn btn-brass" type="submit"><?= $editar ? 'Guardar cambios' : 'Crear usuario' ?></button>
          <a class="btn btn-outline-field" href="<?= url('usuarios') ?>">Cancelar</a>
        </div>
      </form>
    </section>
  </div>

  <?php if ($editar): ?>
  <div class="col-lg-5">
    <section class="panel">
      <h2 class="panel__title mb-1">Restablecer contraseña</h2>
      <form method="post" action="<?= url('usuarios/password/' . (int) $u['id']) ?>" autocomplete="off"
            data-confirm="¿Restablecer la contraseña?" data-confirm-text="La contraseña anterior dejará de funcionar." data-confirm-button="Sí, restablecer">
        <?= csrf_field() ?>
        <label class="form-label" for="pw">Contraseña nueva</label>
        <div class="input-group mb-3">
          <input type="password" class="form-control" id="pw" name="password" minlength="8" required autocomplete="new-password">
          <button class="btn btn-outline-field" type="button" data-toggle-password="#pw" aria-label="Mostrar u ocultar"><i class="bi bi-eye"></i></button>
        </div>
        <button class="btn btn-outline-brass" type="submit">Restablecer</button>
      </form>
    </section>
  </div>
  <?php endif; ?>
</div>
