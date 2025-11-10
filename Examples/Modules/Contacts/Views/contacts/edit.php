<?php $layoutFile = __DIR__ . '/../layout.php'; ?>
<?php $sections->start('content'); ?>
  <h1>Edit Contact</h1>
  <form method="post" action="<?php echo \Ishmael\Core\Router::url('contacts.update', ['id' => (int)($item['id'] ?? 0)]); ?>">
    <?php /* CSRF token placeholder if middleware is wired to validate */ ?>
    <?php include __DIR__ . '/_form.php'; ?>
  </form>
  <form method="post" action="<?php echo \Ishmael\Core\Router::url('contacts.destroy', ['id' => (int)($item['id'] ?? 0)]); ?>" style="margin-top:1rem">
    <button class="btn" type="submit" onclick="return confirm('Delete this contact?');">Delete</button>
  </form>
<?php $sections->end(); ?>
