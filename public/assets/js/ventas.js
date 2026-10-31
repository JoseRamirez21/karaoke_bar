// Carrito de la pantalla "Vender". Solo muestra y suma: el servidor vuelve a calcular todo con los precios reales.
(function () {
  'use strict';
  var tabla  = document.getElementById('tablaVenta');
  var form   = document.getElementById('formVenta');
  if (!tabla || !form) return;

  var lista     = document.getElementById('resumenLista');
  var vacio     = document.getElementById('resumenVacio');
  var totalEl   = document.getElementById('resumenTotal');
  var btn       = document.getElementById('btnCobrar');
  var metodo    = document.getElementById('metodo_id');
  var bloque    = document.getElementById('bloqueEfectivo');
  var recibido  = document.getElementById('recibido');
  var vueltoEl  = document.getElementById('vuelto');
  var total = 0;

  function dinero(n) { return 'S/ ' + n.toFixed(2); }

  // Mantiene la cantidad entre 0 y el stock disponible
  function cantidad(input) {
    var q = parseInt(input.value, 10);
    var max = parseInt(input.max, 10);
    if (isNaN(q) || q < 0) q = 0;
    if (!isNaN(max) && q > max) q = max;
    input.value = q;
    return q;
  }

  function esEfectivo() {
    var o = metodo.options[metodo.selectedIndex];
    return !!o && o.dataset.efectivo === '1';
  }

  function actualizarVuelto() {
    bloque.hidden = !esEfectivo();
    if (!esEfectivo()) { recibido.value = ''; vueltoEl.textContent = ''; return; }
    var r = parseFloat(String(recibido.value).replace(',', '.'));
    if (isNaN(r) || total <= 0) { vueltoEl.textContent = ''; return; }
    var ok = r + 0.004 >= total;
    vueltoEl.textContent = ok ? 'Vuelto: ' + dinero(Math.max(0, r - total)) : 'Faltan ' + dinero(total - r);
    vueltoEl.className = 'vuelto ' + (ok ? 'vuelto--ok' : 'vuelto--falta');
  }

  function recalcular() {
    total = 0;
    lista.innerHTML = '';
    tabla.querySelectorAll('tbody tr[data-id]').forEach(function (tr) {
      var input = tr.querySelector('input[type=number]');
      if (!input) return;
      var q = cantidad(input);
      tr.classList.toggle('is-picked', q > 0);
      if (q === 0) return;
      var sub = parseFloat(tr.dataset.precio) * q;
      total += sub;
      var li = document.createElement('li');
      var a = document.createElement('span');   a.textContent = q + ' × ' + tr.dataset.nombre;   // textContent: nunca se interpreta como HTML
      var b = document.createElement('strong'); b.textContent = dinero(sub);
      li.appendChild(a); li.appendChild(b);
      lista.appendChild(li);
    });
    vacio.hidden = total > 0;
    totalEl.textContent = dinero(total);
    btn.disabled = total <= 0;
    btn.textContent = total > 0 ? 'Cobrar ' + dinero(total) : 'Cobrar';
    actualizarVuelto();
  }

  tabla.addEventListener('click', function (e) {
    var b = e.target.closest('[data-qty]');
    if (!b) return;
    var input = b.parentElement.querySelector('input[type=number]');
    input.value = (parseInt(input.value, 10) || 0) + parseInt(b.dataset.qty, 10);
    recalcular();
  });
  tabla.addEventListener('input', recalcular);
  metodo.addEventListener('change', actualizarVuelto);
  recibido.addEventListener('input', actualizarVuelto);

  // Enter nunca cobra por accidente: solo el botón "Cobrar"
  form.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') e.preventDefault();
  });

  form.addEventListener('submit', function (e) {
    if (total <= 0) { e.preventDefault(); return; }
    btn.disabled = true;               // evita cobrar dos veces con un doble clic
    btn.textContent = 'Cobrando...';
  });

  // Si vuelven con el botón "atrás" del navegador, se recalcula todo
  window.addEventListener('pageshow', recalcular);
  recalcular();
})();