<?php
$editar = $modo === 'editar';
$old    = $GLOBALS['_old'] ?? [];
$mesaSel = (int) ($old['mesa_id'] ?? ($r['mesa_id'] ?? 0));
$fechaV = $old['fecha'] ?? ($editar ? date('Y-m-d', strtotime($r['fecha_hora'])) : date('Y-m-d'));
$horaV  = $old['hora']  ?? ($editar ? date('H:i', strtotime($r['fecha_hora'])) : '');
?>
<header class="page-head page-head--actions">
  <div>
    <h1><?= $editar ? 'Editar reserva' : 'Nueva reserva' ?></h1>
    <p>Aparta una mesa para una fecha y hora. Deja al menos 2 horas entre reservas de la misma mesa.</p>
  </div>
  <a class="btn btn-outline-field" href="<?= url('reservas') ?>"><i class="bi bi-arrow-left"></i> Volver</a>
</header>

<section class="panel" style="max-width:720px">
  <form method="post" action="<?= url($editar ? 'reservas/actualizar/' . (int) $r['id'] : 'reservas/guardar') ?>" autocomplete="off">
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label" for="cliente">Nombre del cliente</label>
        <input class="form-control" id="cliente" name="cliente" maxlength="100" required value="<?= old('cliente', $r['cliente'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="telefono">Teléfono <small>(opcional)</small></label>
        <input class="form-control" id="telefono" name="telefono" maxlength="20" inputmode="tel" value="<?= old('telefono', $r['telefono'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label" for="mesa_id">Mesa</label>
        <select class="form-select" id="mesa_id" name="mesa_id" required>
          <option value="">Elige...</option>
          <?php foreach ($mesas as $m): ?>
            <option value="<?= (int) $m['id'] ?>"<?= sel($mesaSel, $m['id']) ?>>Mesa <?= e($m['numero']) ?> (hasta <?= (int) $m['capacidad'] ?> personas)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="personas">Personas</label>
        <input class="form-control" id="personas" name="personas" type="number" min="1" max="99" required value="<?= old('personas', (string) ($r['personas'] ?? '2')) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="fecha">Fecha</label>
        <input class="form-control" id="fecha" name="fecha" type="date" required value="<?= e($fechaV) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="hora">Hora</label>
        <input class="form-control" id="hora" name="hora" type="time" required value="<?= e($horaV) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="adelanto">Adelanto (S/)</label>
        <input class="form-control" id="adelanto" name="adelanto" inputmode="decimal" value="<?= old('adelanto', isset($r['adelanto']) ? number_format((float) $r['adelanto'], 2, '.', '') : '0.00') ?>">
      </div>
      <div class="col-12">
        <label class="form-label" for="notas">Notas <small>(opcional)</small></label>
        <input class="form-control" id="notas" name="notas" maxlength="255" placeholder="Ej.: cumpleaños, quieren torta" value="<?= old('notas', $r['notas'] ?? '') ?>">
      </div>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button class="btn btn-brass" type="submit"><?= $editar ? 'Guardar cambios' : 'Registrar reserva' ?></button>
      <a class="btn btn-outline-field" href="<?= url('reservas') ?>">Cancelar</a>
    </div>
  </form>
</section>
