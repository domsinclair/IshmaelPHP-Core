<?php $layoutFile = __DIR__ . '/../layout.php'; ?>
<?php $sections->start('content'); ?>
  <h1>Contacts</h1>
  <p>
    <a class="btn btn-primary" href="<?php echo \Ishmael\Core\Router::url('contacts.create'); ?>">New Contact</a>
  </p>
  <table>
    <thead>
      <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach (($items ?? []) as $c): ?>
      <tr>
        <td><?php echo htmlspecialchars(($c['firstName'] ?? '') . ' ' . ($c['lastName'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string)($c['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string)($c['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
          <a class="btn" href="<?php echo \Ishmael\Core\Router::url('contacts.show', ['id' => (int)$c['id']]); ?>">View</a>
          <a class="btn" href="<?php echo \Ishmael\Core\Router::url('contacts.edit', ['id' => (int)$c['id']]); ?>">Edit</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php $sections->end(); ?>
