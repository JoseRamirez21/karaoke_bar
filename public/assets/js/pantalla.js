// Pantalla del karaoke: reproduce la cola de YouTube, una canción tras otra, sin que nadie toque nada.
(function () {
  'use strict';
  var raiz = document.getElementById('pantalla');
  if (!raiz) return;

  var URL_ESTADO = raiz.dataset.estado, URL_SIG = raiz.dataset.siguiente, URL_FIN = raiz.dataset.terminar, CSRF = raiz.dataset.csrf;
  var player = null, listo = false, iniciado = false, idActual = null, ocupado = false, terminando = false, repetir = false;

  var tAhora = document.getElementById('tAhora'), tMesa = document.getElementById('tMesa'), tSig = document.getElementById('tSiguientes');
  var capaInicio = document.getElementById('capaInicio'), capaVacio = document.getElementById('capaVacio');

  // --- Cargar la API de YouTube
  var s = document.createElement('script');
  s.src = 'https://www.youtube.com/iframe_api';
  document.head.appendChild(s);
  window.onYouTubeIframeAPIReady = function () {
    player = new YT.Player('reproductor', {
      width: '100%', height: '100%',
      playerVars: { autoplay: 1, rel: 0, playsinline: 1, modestbranding: 1 },
      events: {
        onReady: function () { listo = true; },
        onStateChange: function (e) { if (e.data === YT.PlayerState.ENDED) fin(false); },
        onError: function () { fin(true); }               // video bloqueado, borrado o sin permiso: pasa a la siguiente
      }
    });
  };

  function post(url, datos) {
    var b = new URLSearchParams(datos || {});
    b.append('_csrf', CSRF);
    return fetch(url, { method: 'POST', body: b, headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); });
  }

  function fin(conError) {
    if (terminando || idActual === null) return;
    terminando = true;
    var id = idActual;
    post(URL_FIN, { id: id, resultado: conError ? 'error' : 'ok' })
      .catch(function () {})
      .then(function () {
        idActual = null; terminando = false;
        if (ocupado) { repetir = true; } else { ciclo(); }     // si justo hay una consulta en curso, se repite apenas termine
      });
  }

  function pintar(d) {
    tAhora.textContent = d.ahora ? d.ahora.titulo : '-';
    tMesa.textContent  = d.ahora ? 'Mesa ' + d.ahora.mesa : '';
    tSig.innerHTML = '';
    d.siguientes.forEach(function (c) {
      var li = document.createElement('li');
      var a = document.createElement('span'); a.textContent = c.titulo;       // textContent: nunca se interpreta como HTML
      var b = document.createElement('small'); b.textContent = 'Mesa ' + c.mesa;
      li.appendChild(a); li.appendChild(b); tSig.appendChild(li);
    });
    capaVacio.hidden = !iniciado || !!d.ahora || d.pendientes > 0;
  }

  function ciclo() {
    if (ocupado) return;
    ocupado = true;
    fetch(URL_ESTADO, { cache: 'no-store', headers: { Accept: 'application/json' } })
      .then(function (r) {
        // Si la sesión venció, el servidor responde con la página de ingreso en vez de datos: recargamos para pedir que inicien sesión.
        if (r.redirected || (r.headers.get('content-type') || '').indexOf('json') === -1) { location.reload(); throw new Error('sesion'); }
        return r.json();
      })
      .then(function (d) {
        pintar(d);
        if (!listo || !iniciado || terminando) return;
        if (d.ahora) {
          if (d.ahora.id !== idActual) { idActual = d.ahora.id; player.loadVideoById(d.ahora.video_id); }
        } else if (idActual !== null) {                     // el personal saltó la última canción
          idActual = null; player.stopVideo();
        } else if (d.pendientes > 0) {                      // nada suena y hay fila: arrancar la siguiente
          return post(URL_SIG).then(function () { repetir = true; });
        }
      })
      .catch(function () {})
      .then(function () {
        ocupado = false;
        if (repetir) { repetir = false; ciclo(); }          // vuelve a consultar de inmediato para cargar el video sin esperar 3 segundos
      });
  }

  document.getElementById('btnInicio').addEventListener('click', function () {
    iniciado = true;
    capaInicio.hidden = true;
    ciclo();
  });
  document.getElementById('btnPantallaCompleta').addEventListener('click', function () {
    var r = document.documentElement;
    if (!document.fullscreenElement) { (r.requestFullscreen || function () {}).call(r); } else { document.exitFullscreen(); }
  });

  setInterval(ciclo, 3000);
  ciclo();
})();