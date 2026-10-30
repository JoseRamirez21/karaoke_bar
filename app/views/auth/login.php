<section class="login">
  <div class="login__brand">
    <span class="brand-mark brand-mark--lg"><i class="bi bi-mic-fill"></i></span>
    <h1><?= e(Config::get('nombre_local', 'Karaoke Bar')) ?></h1>
    <p>Ingresa para abrir tu turno.</p>
  </div>

  <form method="post" action="<?= url('auth/login') ?>" class="login__form" autocomplete="off" novalidate>
    <?= csrf_field() ?>
    <div class="mb-3">
      <label for="usuario" class="form-label">Usuario</label>
      <input type="text" class="form-control form-control-lg" id="usuario" name="usuario" value="<?= old('usuario') ?>" required autofocus autocapitalize="off">
    </div>
    <div class="mb-4">
      <label for="password" class="form-label">Contraseña</label>
      <div class="input-group input-group-lg">
        <input type="password" class="form-control" id="password" name="password" required>
        <button class="btn btn-outline-field" type="button" data-toggle-password="#password" aria-label="Mostrar u ocultar contraseña"><i class="bi bi-eye"></i></button>
      </div>
    </div>
    <button type="submit" class="btn btn-brass btn-lg w-100">Entrar</button>
  </form>
</section>
