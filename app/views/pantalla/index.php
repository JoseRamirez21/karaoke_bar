<div id="pantalla" data-estado="<?= url('pantalla/estado') ?>" data-siguiente="<?= url('pantalla/siguiente') ?>"
     data-terminar="<?= url('pantalla/terminar') ?>" data-csrf="<?= e($csrf) ?>">
  <div class="tv">
    <div class="tv__video">
      <div id="reproductor"></div>

      <div class="tv__capa" id="capaInicio">
        <h1><?= e(Config::get('nombre_local', 'Karaoke Bar')) ?></h1>
        <button class="btn btn-brass btn-lg" id="btnInicio" type="button">Iniciar karaoke</button>
        <p>Pulsa una vez para activar el sonido. Después funciona solo.</p>
      </div>

      <div class="tv__capa tv__capa--vacio" id="capaVacio" hidden>
        <h1>¡Es hora de cantar!</h1>
        <p>Escanea el QR de tu mesa y elige tu canción.</p>
      </div>
    </div>

    <aside class="tv__lado">
      <p class="tv__etiqueta">Ahora suena</p>
      <h2 id="tAhora">-</h2>
      <p class="tv__mesa" id="tMesa"></p>

      <p class="tv__etiqueta mt-4">Siguientes</p>
      <ol class="tv__lista" id="tSiguientes"></ol>

      <button class="btn btn-outline-field btn-sm mt-3" id="btnPantallaCompleta" type="button">Pantalla completa</button>
    </aside>
  </div>
</div>