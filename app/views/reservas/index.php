<?php
$vistas = ['proximas' => 'Próximas', 'hoy' => 'Hoy', 'historial' => 'Historial'];
$badge  = ['pendiente' => ['brass', 'Pendiente'], 'confirmada' => ['ok', 'Confirmada'], 'cumplida' => ['plain', 'Cumplida'], 'cancelada' => ['danger', 'Cancelada'], 'no_asistio' => ['off', 'No asistió']];
?>
<header class="page-head page-head--actions">
  <div>
    <h1>Reservas</h1>
    <p>Las mesas apartadas por los clientes. La mesa aparece "reservada" en el salón desde 1 hora antes.</p>
  </div>
  <a class="btn btn-brass" href="<?= url('reservas/crear') ?>"><i class="bi bi-plus-lg"></i> Nueva reserva</a>
</header>

<ul class="nav nav-pills mb-3 gap-1">
  <?php foreach ($vistas as $k => $txt): ?>
    <li class="nav-item"><a class="btn btn-sm <?= $vista === $k ? 'btn-brass' : 'btn-outline-field' ?>" href="<?= url('reservas?ver=' . $k) ?>"><?= $txt ?></a></li>
  <?php endforeach; ?>
</ul>

<section class="panel panel--flush">
  <div class="table-responsive">
    <table class="table table-app align-middle mb-0">
      <thead><tr><th>Cuándo</th><th>Mesa</th><th>Cliente</th><th class="text-end">Personas</th><th class="text-end">Adelanto</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
      <tbody>
      <?php foreach ($reservas as $r):
          [$cls, $txt] = $badge[$r['estado']] ?? ['plain', $r['estado']];
          $activa = in_array($r['estado'], ['pendiente', 'confirmada'], true); ?>
        <tr class="<?= $activa ? '' : 'is-off' ?>">
          <td><strong><?= e(hora12($r['fecha_hora'])) ?></strong><small class="d-block text-secondary"><?= e(date('d/m/Y', strtotime($r['fecha_hora']))) ?></small></td>
          <td>Mesa <?= e($r['mesa']) ?></td>
          <td><?= e($r['cliente']) ?><small class="d-block text-secondary"><?= e($r['telefono'] ?: 'Sin teléfono') ?><?= $r['notas'] ? ', ' . e($r['notas']) : '' ?></small></td>
          <td class="text-end"><?= (int) $r['personas'] ?></td>
          <td class="text-end"><?= (float) $r['adelanto'] > 0 ? e(money($r['adelanto'])) : '-' ?></td>
          <td><span class="badge-soft badge-soft--<?= $cls ?>"><?= e($txt) ?></span></td>
          <td class="text-end actions">
            <?php if ($activa): ?>
              <a class="btn btn-sm btn-brass" href="<?= url('mesas/abrir/' . (int) $r['mesa_id'] . '?reserva=' . (int) $r['id']) ?>">Abrir mesa</a>
              <?php if ($r['estado'] === 'pendiente'): ?>
                <form method="post" action="<?= url('reservas/estado/' . (int) $r['id']) ?>" class="d-inline"><?= csrf_field() ?><input type="hidden" name="nuevo" value="confirmada">
                  <button class="btn btn-sm btn-outline-field" type="submit">Confirmar</button></form>
              <?php endif; ?>
              <a class="btn btn-sm btn-outline-field" href="<?= url('reservas/editar/' . (int) $r['id']) ?>" title="Editar"><i class="bi bi-pencil"></i></a>
              <form method="post" action="<?= url('reservas/estado/' . (int) $r['id']) ?>" class="d-inline" data-confirm="¿Marcar que no asistió?" data-confirm-text="La mesa se libera para otros clientes." data-confirm-button="Sí, no asistió">
                <?= csrf_field() ?><input type="hidden" name="nuevo" value="no_asistio"><button class="btn btn-sm btn-outline-field" type="submit">No asistió</button></form>
              <form method="post" action="<?= url('reservas/estado/' . (int) $r['id']) ?>" class="d-inline" data-confirm="¿Cancelar la reserva de <?= e($r['cliente']) ?>?" data-confirm-button="Sí, cancelar">
                <?= csrf_field() ?><input type="hidden" name="nuevo" value="cancelada"><button class="btn btn-sm btn-outline-field" type="submit">Cancelar</button></form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (!$reservas): ?><p class="empty m-4">No hay reservas en esta vista.</p><?php endif; ?>
</section>
