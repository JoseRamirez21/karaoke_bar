<?php
$esLocal = (bool) preg_match('#^https?://(localhost|127\.0\.0\.1)([:/]|$)#i', $direccion);
$local   = Config::get('nombre_local', 'Karaoke Bar');
?>
<div class="no-print">
  <header class="page-head page-head--actions">
    <div>
      <h1><?= $unaSola ? 'QR de la mesa ' . e($mesas[0]['numero']) : 'Códigos QR de las mesas' ?></h1>
      <p>Imprímelos y pégalos en cada mesa. Los clientes lo escanean para pedir su música.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a class="btn btn-outline-field" href="<?= url('mesas') ?>"><i class="bi bi-arrow-left"></i> Volver</a>
      <button class="btn btn-brass" type="button" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
    </div>
  </header>

  <section class="panel mb-4">
    <h2 class="panel__title mb-1">Dirección de este sistema en tu red</h2>
    <p class="text-secondary">Los celulares de los clientes abren esta dirección al escanear. Deben estar conectados al Wi-Fi del local.</p>
    <?php if ($esLocal): ?>
      <div class="notice notice--warn">
        <i class="bi bi-wifi"></i>
        <span><strong>"localhost" no sirve para los celulares.</strong> Escribe la IP de esta PC en tu red. Para verla: abre <em>CMD</em>, escribe <em>ipconfig</em> y copia la "Dirección IPv4" (algo como 192.168.1.50). La dirección quedaría <em>http://192.168.1.50/karaoke_bar</em></span>
      </div>
    <?php endif; ?>
    <form method="post" action="<?= url('mesas/direccion') ?>" class="row g-2 align-items-end" autocomplete="off">
      <?= csrf_field() ?>
      <div class="col-md"><input class="form-control" name="direccion" value="<?= e($direccion) ?>" aria-label="Dirección" placeholder="http://192.168.1.50/karaoke_bar" required></div>
      <div class="col-md-auto"><button class="btn btn-outline-brass w-100" type="submit">Guardar dirección</button></div>
    </form>
  </section>
</div>

<?php if (!$mesas): ?>
  <p class="empty">No hay mesas activas para imprimir.</p>
<?php else: ?>
  <div class="qr-grid">
    <?php foreach ($mesas as $m): ?>
      <article class="qr-card">
        <p class="qr-card__local"><?= e($local) ?></p>
        <h2 class="qr-card__mesa">Mesa <?= e($m['numero']) ?></h2>
        <div class="qr-box" data-url="<?= e(rtrim($direccion, '/') . '/musica/mesa/' . $m['qr_token']) ?>"></div>
        <p class="qr-card__text">Escanea y elige tu música</p>
        <form method="post" action="<?= url('mesas/token/' . (int) $m['id']) ?>" class="no-print mt-2"
              data-confirm="¿Generar un QR nuevo para la mesa <?= e($m['numero']) ?>?" data-confirm-text="El QR impreso actual dejará de funcionar." data-confirm-button="Sí, generar">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-outline-field" type="submit"><i class="bi bi-arrow-repeat"></i> Generar QR nuevo</button>
        </form>
      </article>
    <?php endforeach; ?>
  </div>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script>
    document.querySelectorAll('.qr-box').forEach(function (el) {
      new QRCode(el, { text: el.dataset.url, width: 190, height: 190, colorDark: '#000000', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });
    });
  </script>
<?php endif; ?>
