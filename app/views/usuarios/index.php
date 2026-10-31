<header class="page-head page-head--actions">
  <div>
    <h1>Usuarios</h1>
    <p>Las personas que pueden entrar al sistema. Solo tú, como administrador, puedes crearlas.</p>
  </div>
  <a class="btn btn-brass" href="<?= url('usuarios/crear') ?>"><i class="bi bi-plus-lg"></i> Nuevo usuario</a>
</header>

<section class="panel panel--flush">
  <div class="table-responsive">
    <table class="table table-app align-middle mb-0">
      <thead><tr><th>Nombre</th><th>Rol</th><th>Estado</th><th>Último ingreso</th><th class="text-end">Acciones</th></tr></thead>
      <tbody>
      <?php foreach ($usuarios as $u): $yo = (int) $u['id'] === Auth::id(); ?>
        <tr class="<?= (int) $u['estado'] ? '' : 'is-off' ?>">
          <td>
            <strong><?= e($u['nombre']) ?></strong><?= $yo ? ' <span class="tag">Tú</span>' : '' ?>
            <small class="d-block text-secondary"><?= e($u['usuario']) ?><?= $u['telefono'] ? ', ' . e($u['telefono']) : '' ?></small>
          </td>
          <td><span class="badge-soft badge-soft--<?= $u['rol'] === 'admin' ? 'brass' : 'plain' ?>"><?= $u['rol'] === 'admin' ? 'Administrador' : 'Cajero' ?></span></td>
          <td><span class="badge-soft badge-soft--<?= (int) $u['estado'] ? 'ok' : 'off' ?>"><?= (int) $u['estado'] ? 'Activo' : 'Desactivado' ?></span></td>
          <td class="text-secondary"><?= e(fecha_corta($u['ultimo_login'])) ?></td>
          <td class="text-end actions">
            <a class="btn btn-sm btn-outline-field" href="<?= url('usuarios/editar/' . (int) $u['id']) ?>"><i class="bi bi-pencil"></i> Editar</a>
            <?php if (!$yo): ?>
              <form method="post" action="<?= url('usuarios/estado/' . (int) $u['id']) ?>" class="d-inline"
                    data-confirm="<?= (int) $u['estado'] ? '¿Desactivar a ' . e($u['nombre']) . '?' : '¿Activar a ' . e($u['nombre']) . '?' ?>"
                    data-confirm-text="<?= (int) $u['estado'] ? 'Ya no podrá ingresar al sistema. Su historial se conserva.' : 'Podrá ingresar de nuevo con su contraseña.' ?>"
                    data-confirm-button="<?= (int) $u['estado'] ? 'Sí, desactivar' : 'Sí, activar' ?>">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-field" type="submit">
                  <?= (int) $u['estado'] ? '<i class="bi bi-person-dash"></i> Desactivar' : '<i class="bi bi-person-check"></i> Activar' ?>
                </button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<p class="form-hint">Los usuarios no se borran, solo se desactivan: así las ventas que hicieron siguen mostrando quién las cobró.</p>
