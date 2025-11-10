<?php $layoutFile = __DIR__ . '/../layout.php'; ?>
<?php $sections->start('content'); ?>
  <h1>Edit User</h1>
  <form method="post" action="<?php echo route('users.update', ['id' => (int)($user['id'] ?? 0)]); ?>">
    <?php echo function_exists('csrfField') ? csrfField() : ''; ?>
    <?php include __DIR__ . '/_form.php'; ?>
  </form>

  <h2>Roles</h2>
  <form method="post" action="<?php echo route('users.attachRole', ['id' => (int)($user['id'] ?? 0)]); ?>" style="margin-bottom:1rem">
    <?php echo function_exists('csrfField') ? csrfField() : ''; ?>
    <label>Assign role
      <select name="role_id">
        <?php foreach (($roles ?? []) as $r): ?>
          <option value="<?php echo (int)$r['id']; ?>"><?php echo e($r['name']); ?> (<?php echo e($r['slug']); ?>)</option>
        <?php endforeach; ?>
      </select>
    </label>
    <button class="btn">Attach</button>
  </form>

  <ul>
    <?php foreach (($userRoles ?? []) as $r): ?>
      <li>
        <?php echo e($r['name']); ?> (<?php echo e($r['slug']); ?>)
        <form method="post" action="<?php echo route('users.detachRole', ['id' => (int)($user['id'] ?? 0)]); ?>" style="display:inline">
          <?php echo function_exists('csrfField') ? csrfField() : ''; ?>
          <input type="hidden" name="role_id" value="<?php echo (int)$r['id']; ?>">
          <button class="btn">Remove</button>
        </form>
      </li>
    <?php endforeach; ?>
  </ul>
<?php $sections->end(); ?>
