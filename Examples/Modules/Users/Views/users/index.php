<?php $layoutFile = __DIR__ . '/../layout.php'; ?>
<?php $sections->start('content'); ?>
  <h1>Users</h1>
  <p><a class="btn" href="<?php echo route('users.create'); ?>">New</a></p>
  <form method="get" action="<?php echo route('users.index'); ?>" style="margin-bottom:1rem">
    <input type="text" name="q" value="<?php echo e($query ?? ''); ?>" placeholder="Search name or email">
    <button class="btn">Search</button>
  </form>
  <table>
    <thead><tr><th>ID</th><th>Name</th><th>Email</th><th></th></tr></thead>
    <tbody>
    <?php foreach (($items ?? []) as $u): ?>
      <tr>
        <td><?php echo (int)$u['id']; ?></td>
        <td><?php echo e($u['name']); ?></td>
        <td><?php echo e($u['email']); ?></td>
        <td>
          <a class="btn" href="<?php echo route('users.edit', ['id' => (int)$u['id']]); ?>">Edit</a>
          <form method="post" action="<?php echo route('users.destroy', ['id' => (int)$u['id']]); ?>" style="display:inline" onsubmit="return confirm('Delete this user?')">
            <?php echo function_exists('csrfField') ? csrfField() : ''; ?>
            <button class="btn">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php $sections->end(); ?>
