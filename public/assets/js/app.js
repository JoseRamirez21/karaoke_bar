(function () {
  'use strict';

  // Tema de SweetAlert acorde al sistema
  var Sw = Swal.mixin({
    background: '#2D1626',
    color: '#F4EADF',
    confirmButtonColor: '#C9A15B',
    cancelButtonColor: '#4A2A3F',
    customClass: { confirmButton: 'swal-confirm' }
  });
  var Toast = Sw.mixin({
    toast: true, position: 'top-end', showConfirmButton: false,
    timer: 3500, timerProgressBar: true
  });
  window.Sw = Sw; window.Toast = Toast;

  // Mensajes flash del servidor
  (window.__FLASH__ || []).forEach(function (f) {
    if (f.type === 'error') {
      Sw.fire({ icon: 'error', title: 'No se pudo completar', text: f.msg, confirmButtonText: 'Entendido' });
    } else {
      Toast.fire({ icon: f.type === 'warning' ? 'warning' : 'success', title: f.msg });
    }
  });

  // <form data-confirm="¿Eliminar?"> pide confirmación antes de enviar (reutilizable en todos los CRUD)
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      Sw.fire({
        title: form.dataset.confirm,
        text: form.dataset.confirmText || '',
        icon: 'question',
        showCancelButton: true,
        reverseButtons: true,
        confirmButtonText: form.dataset.confirmButton || 'Sí, continuar',
        cancelButtonText: 'Cancelar'
      }).then(function (r) { if (r.isConfirmed) form.submit(); });
    });
  });

  // Mostrar / ocultar contraseña
  document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.querySelector(btn.dataset.togglePassword);
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  });

  // Filtros de tabla: <input data-filter-text="idTabla"> y <select data-filter-attr="idTabla" data-attr="cat">
  // Cada <tr> trae data-search y los data-* que se quieran filtrar.
  var tablas = {};
  function aplicarFiltros(id) {
    var tabla = document.getElementById(id);
    if (!tabla) return;
    var texto = '';
    var input = document.querySelector('[data-filter-text="' + id + '"]');
    if (input) texto = input.value.trim().toLowerCase();
    var selects = document.querySelectorAll('[data-filter-attr="' + id + '"]');
    var visibles = 0;
    tabla.querySelectorAll('tbody tr').forEach(function (tr) {
      var ok = !texto || (tr.dataset.search || '').indexOf(texto) !== -1;
      selects.forEach(function (sel) {
        if (sel.value && tr.dataset[sel.dataset.attr] !== sel.value) ok = false;
      });
      tr.hidden = !ok;
      if (ok) visibles++;
    });
    var vacio = document.getElementById('sinResultados');
    if (vacio) vacio.hidden = visibles > 0 || !tabla.querySelector('tbody tr');
  }
  document.querySelectorAll('[data-filter-text], [data-filter-attr]').forEach(function (el) {
    var id = el.dataset.filterText || el.dataset.filterAttr;
    el.addEventListener(el.tagName === 'SELECT' ? 'change' : 'input', function () { aplicarFiltros(id); });
  });
})();
