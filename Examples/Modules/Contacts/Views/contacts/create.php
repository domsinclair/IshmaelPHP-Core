<?php $layoutFile = __DIR__ . '/../layout.php'; ?>
<?php $sections->start('content'); ?>
  <h1>New Contact</h1>
  <form method="post" action="<?php echo \Ishmael\Core\Router::url('contacts.store'); ?>">
    <?php /* CSRF token placeholder if middleware is wired to validate */ ?>
    <?php include __DIR__ . '/_form.php'; ?>
  </form>
<?php $sections->end(); ?>
