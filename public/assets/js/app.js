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
})();
