<?php $layoutFile = __DIR__ . '/../layout.php'; ?>
<?php $sections->start('content'); ?>
  <h1>New User</h1>
  <form method="post" action="<?php echo route('users.store'); ?>">
    <?php echo function_exists('csrfField') ? csrfField() : ''; ?>
    <?php $user = null; include __DIR__ . '/_form.php'; ?>
  </form>
<?php $sections->end(); ?>
