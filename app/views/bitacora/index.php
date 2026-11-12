<?php
$consulta = fn(array $extra) => http_build_query(array_filter(array_merge([
    'accion' => $f['accion'], 'usuario' => $f['usuario_id'] ?: '', 'desde' => $f['desde'], 'hasta' => $f['hasta'], 'q' => $f['q'],
], $extra), fn($v) => $v !== '' && $v !== null));
$rojas = ['login_fallido', 'venta_anulada', 'usuario_desactivado', 'precio_cambiado', 'stock_ajuste', 'stock_salida'];
?>
<header class="page-head">
  <h1>Bitácora</h1>
  <p>El registro de todo lo importante que pasa en el sistema: quién hizo qué y cuándo. No se puede borrar ni editar.</p>
</header>

<form method="get" action="<?= url('bitacora') ?>" class="toolbar align-items-end">
  <div><label class="form-label mb-1" for="accion">Acción</label>
    <select class="form-select" id="accion" name="accion">
      <option value="">Todas</option>
      <?php foreach ($acciones as $a): ?><option value="<?= e($a) ?>"<?= sel($f['accion'], $a) ?>><?= e($etiquetas[$a] ?? $a) ?></option><?php endforeach; ?>
    </select></div>
  <div><label class="form-label mb-1" for="usuario">Usuario</label>
    <select class="form-select" id="usuario" name="usuario">
      <option value="">Todos</option>
      <?php foreach ($usuarios as $u): ?><option value="<?= (int) $u['id'] ?>"<?= sel($f['usuario_id'], $u['id']) ?>><?= e($u['nombre']) ?></option><?php endforeach; ?>
    </select></div>
  <div><label class="form-label mb-1" for="desde">Desde</label><input class="form-control" type="date" id="desde" name="desde" value="<?= e($f['desde']) ?>"></div>
  <div><label class="form-label mb-1" for="hasta">Hasta</label><input class="form-control" type="date" id="hasta" name="hasta" value="<?= e($f['hasta']) ?>"></div>
  <div class="flex-grow-1"><label class="form-label mb-1" for="q">Buscar en el detalle</label><input class="form-control" id="q" name="q" maxlength="60" value="<?= e($f['q']) ?>" placeholder="Ej.: T-000015"></div>
  <div class="d-flex gap-2"><button class="btn btn-brass" type="submit">Filtrar</button><a class="btn btn-outline-field" href="<?= url('bitacora') ?>">Limpiar</a></div>
</form>

<section class="panel panel--flush">
  <div class="table-responsive">
    <table class="table table-app align-middle mb-0">
      <thead><tr><th>Fecha y hora</th><th>Usuario</th><th>Acción</th><th>Detalle</th><th>IP</th></tr></thead>
      <tbody>
      <?php foreach ($filas as $b): ?>
        <tr>
          <td class="text-nowrap"><?= e(fecha_corta($b['created_at'])) ?></td>
          <td><?= $b['usuario'] ? e($b['usuario']) : '<span class="text-secondary">Sin usuario</span>' ?></td>
          <td><span class="badge-soft badge-soft--<?= in_array($b['accion'], $rojas, true) ? 'brass' : 'plain' ?>"><?= e($etiquetas[$b['accion']] ?? $b['accion']) ?></span></td>
          <td class="text-secondary"><?= e($b['detalle'] ?? '') ?></td>
          <td class="text-secondary text-nowrap"><?= e($b['ip'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (!$filas): ?><p class="empty m-4">No hay registros con esos filtros.</p><?php endif; ?>
</section>

<?php if ($paginas > 1): ?>
  <nav class="d-flex justify-content-between align-items-center mt-3" aria-label="Páginas">
    <span class="text-secondary"><?= (int) $total ?> registros, página <?= (int) $pagina ?> de <?= (int) $paginas ?></span>
    <div class="d-flex gap-2">
      <?php if ($pagina > 1): ?><a class="btn btn-sm btn-outline-field" href="<?= url('bitacora?' . $consulta(['p' => $pagina - 1])) ?>"><i class="bi bi-chevron-left"></i> Anterior</a><?php endif; ?>
      <?php if ($pagina < $paginas): ?><a class="btn btn-sm btn-outline-field" href="<?= url('bitacora?' . $consulta(['p' => $pagina + 1])) ?>">Siguiente <i class="bi bi-chevron-right"></i></a><?php endif; ?>
    </div>
  </nav>
<?php else: ?>
  <p class="text-secondary mt-3"><?= (int) $total ?> registro<?= $total === 1 ? '' : 's' ?>.</p>
<?php endif; ?>
