<?php $layoutFile = __DIR__ . '/../layout.php'; ?>
<?php $sections->start('content'); ?>
  <h1>Login</h1>
  <form method="post" action="<?php echo route('auth.login'); ?>">
    <?php echo function_exists('csrfField') ? csrfField() : ''; ?>
    <div class="field">
      <label>Email</label>
      <input name="email" type="email" required>
    </div>
    <div class="field">
      <label>Password</label>
      <input name="password" type="password" required>
    </div>
    <button class="btn btn-primary">Sign in</button>
  </form>
<?php $sections->end(); ?>
