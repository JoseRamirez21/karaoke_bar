// Celular del cliente: mantiene al día "Ahora suena" y "Tus canciones" sin recargar la página.
(function () {
  'use strict';
  var raiz = document.getElementById('musica');
  if (!raiz || raiz.dataset.abierta !== '1') return;

  var ahoraT = document.getElementById('ahoraTitulo');
  var ahoraM = document.getElementById('ahoraMesa');
  var mias   = document.getElementById('mias');
  var cupo   = document.getElementById('cupo');

  function el(tag, clase, texto) {
    var n = document.createElement(tag);
    if (clase) n.className = clase;
    if (texto !== undefined) n.textContent = texto;     // textContent: los títulos nunca se interpretan como HTML
    return n;
  }

  function pintar(d) {
    if (!d.abierta) { location.reload(); return; }       // cerraron la mesa: la página muestra el aviso
    ahoraT.textContent = d.ahora ? d.ahora.titulo : 'Nada por ahora. ¡Anímate a cantar!';
    ahoraM.textContent = d.ahora ? 'Mesa ' + d.ahora.mesa : '';
    cupo.textContent = d.usadas + ' de ' + d.max;

    mias.innerHTML = '';
    if (!d.mias.length) { mias.appendChild(el('p', 'empty mb-0', 'Aún no pides nada. Pega un enlace arriba.')); return; }
    d.mias.forEach(function (c) {
      var fila = el('div', 'cancion');
      var img = document.createElement('img'); img.src = c.miniatura; img.alt = ''; img.loading = 'lazy';
      var t = el('div', 'cancion__t');
      t.appendChild(el('strong', '', c.titulo));
      t.appendChild(el('span', '', c.sonando ? 'Sonando ahora' : (c.posicion === 1 ? 'Sigue tu canción' : 'Puesto ' + c.posicion + ' en la fila')));
      fila.appendChild(img); fila.appendChild(t);
      if (!c.sonando) {
        var f = document.createElement('form'); f.method = 'post'; f.action = raiz.dataset.quitar;
        var h1 = document.createElement('input'); h1.type = 'hidden'; h1.name = '_csrf'; h1.value = raiz.dataset.csrf;
        var h2 = document.createElement('input'); h2.type = 'hidden'; h2.name = 'id'; h2.value = c.id;
        var b = el('button', 'btn btn-sm btn-outline-field', 'Quitar'); b.type = 'submit';
        f.appendChild(h1); f.appendChild(h2); f.appendChild(b); fila.appendChild(f);
      }
      mias.appendChild(fila);
    });
  }

  function cargar() {
    if (document.hidden) return;                         // con la pantalla apagada no gasta datos
    fetch(raiz.dataset.estado, { cache: 'no-store', headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json(); }).then(pintar).catch(function () {});
  }
  setInterval(cargar, 6000);
  document.addEventListener('visibilitychange', cargar);
})();