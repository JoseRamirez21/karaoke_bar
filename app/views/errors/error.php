<section class="error-page">
  <p class="error-code"><?= (int) $code ?></p>
  <h1><?= e($title) ?></h1>
  <p class="text-secondary"><?= e($message) ?></p>
  <?php if ($code === 500 && APP_ENV === 'development'): ?>
    <p class="text-secondary"><small>¿Recién instalado? Abre <a href="<?= url('diagnostico.php') ?>">el diagnóstico</a> para ver qué falta.</small></p>
  <?php endif; ?>
  <a class="btn btn-brass" href="<?= url(Auth::check() ? 'dashboard' : 'auth/login') ?>">
    <?= Auth::check() ? 'Volver al panel' : 'Ir a ingresar' ?>
  </a>
</section>
