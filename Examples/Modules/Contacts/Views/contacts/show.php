<?php $layoutFile = __DIR__ . '/../layout.php'; ?>
<?php $sections->start('content'); ?>
  <h1>Contact</h1>
  <p><strong>Name:</strong> <?php echo htmlspecialchars(($item['firstName'] ?? '') . ' ' . ($item['lastName'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
  <p><strong>Email:</strong> <?php echo htmlspecialchars((string)($item['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
  <p><strong>Phone:</strong> <?php echo htmlspecialchars((string)($item['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
  <p><strong>Notes:</strong><br><?php echo nl2br(htmlspecialchars((string)($item['notes'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></p>
  <p>
    <a class="btn" href="<?php echo \Ishmael\Core\Router::url('contacts.edit', ['id' => (int)($item['id'] ?? 0)]); ?>">Edit</a>
    <a class="btn" href="<?php echo \Ishmael\Core\Router::url('contacts.index'); ?>">Back</a>
  </p>
<?php $sections->end(); ?>
