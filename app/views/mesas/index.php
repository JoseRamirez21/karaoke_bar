<?php
$nombreEstado = ['libre' => 'Libre', 'ocupada' => 'Ocupada', 'reservada' => 'Reservada', 'inactiva' => 'Inactiva'];
?>
<header class="page-head page-head--actions">
  <div>
    <h1>Salón y mesas</h1>
    <p><?= $esAdmin ? 'Las mesas del local y el código QR de cada una.' : 'El estado de las mesas del local.' ?></p>
  </div>
  <?php if ($esAdmin): ?>
    <div class="d-flex gap-2 flex-wrap">
      <a class="btn btn-outline-field" href="<?= url('mesas/qr') ?>"><i class="bi bi-qr-code"></i> Imprimir QR</a>
      <a class="btn btn-brass" href="<?= url('mesas/crear') ?>"><i class="bi bi-plus-lg"></i> Nueva mesa</a>
    </div>
  <?php endif; ?>
</header>

<?php if (!$mesas): ?>
  <section class="panel"><p class="empty mb-0">Aún no hay mesas<?= $esAdmin ? '. Crea la primera con el botón "Nueva mesa".' : '.' ?></p></section>
<?php else: ?>
  <section class="panel panel--stage mb-4">
    <p class="text-secondary mb-3">Toca una mesa libre para abrirla, o una ocupada para ver su cuenta.</p>
    <div class="floor">
      <?php foreach ($mesas as $m): if ($m['estado'] === 'inactiva') continue;
          $href = $m['sesion_id'] ? url('mesas/cuenta/' . (int) $m['sesion_id']) : url('mesas/abrir/' . (int) $m['id']); ?>
        <a class="mesa mesa--<?= e($m['estado']) ?>" href="<?= $href ?>" title="Mesa <?= e($m['numero']) ?>, para <?= (int) $m['capacidad'] ?> personas">
          <span class="mesa__n"><?= e($m['numero']) ?></span>
          <span class="mesa__e"><?= e($nombreEstado[$m['estado']] ?? $m['estado']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
    <ul class="legend">
      <li><i class="dot dot--libre"></i>Libre</li><li><i class="dot dot--ocupada"></i>Ocupada</li><li><i class="dot dot--reservada"></i>Reservada</li>
    </ul>
  </section>

  <?php if ($esAdmin): ?>
  <section class="panel panel--flush">
    <div class="table-responsive">
      <table class="table table-app align-middle mb-0">
        <thead><tr><th>Mesa</th><th>Capacidad</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($mesas as $m): ?>
          <tr class="<?= $m['estado'] === 'inactiva' ? 'is-off' : '' ?>">
            <td><strong>Mesa <?= e($m['numero']) ?></strong></td>
            <td><?= (int) $m['capacidad'] ?> personas</td>
            <td><span class="badge-soft badge-soft--<?= ['libre' => 'ok', 'ocupada' => 'rose', 'reservada' => 'violet', 'inactiva' => 'off'][$m['estado']] ?? 'plain' ?>"><?= e($nombreEstado[$m['estado']] ?? $m['estado']) ?></span></td>
            <td class="text-end actions">
              <a class="btn btn-sm btn-outline-field" href="<?= url('mesas/qr/' . (int) $m['id']) ?>"><i class="bi bi-qr-code"></i> QR</a>
              <a class="btn btn-sm btn-outline-field" href="<?= url('mesas/editar/' . (int) $m['id']) ?>"><i class="bi bi-pencil"></i> Editar</a>
              <?php if (in_array($m['estado'], ['libre', 'inactiva'], true)): ?>
              <form method="post" action="<?= url('mesas/estado/' . (int) $m['id']) ?>" class="d-inline"
                    data-confirm="<?= $m['estado'] === 'inactiva' ? '¿Activar la mesa ' . e($m['numero']) . '?' : '¿Desactivar la mesa ' . e($m['numero']) . '?' ?>"
                    data-confirm-text="<?= $m['estado'] === 'inactiva' ? '' : 'Dejará de aparecer en el salón y su QR no funcionará.' ?>" data-confirm-button="Sí, continuar">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-field" type="submit"><?= $m['estado'] === 'inactiva' ? 'Activar' : 'Desactivar' ?></button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <?php endif; ?>
<?php endif; ?>
