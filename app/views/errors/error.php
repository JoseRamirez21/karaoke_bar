<section class="error-page">
  <p class="error-code"><?= (int) $code ?></p>
  <h1><?= e($title) ?></h1>
  <p class="text-secondary"><?= e($message) ?></p>
  <a class="btn btn-brass" href="<?= url(Auth::check() ? 'dashboard' : 'auth/login') ?>">
    <?= Auth::check() ? 'Volver al panel' : 'Ir a ingresar' ?>
  </a>
</section>
