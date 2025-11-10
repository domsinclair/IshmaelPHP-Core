<div class="field">
  <label>Name</label>
  <input name="name" type="text" value="<?php echo isset($user['name']) ? e($user['name']) : ''; ?>" required>
</div>
<div class="field">
  <label>Email</label>
  <input name="email" type="email" value="<?php echo isset($user['email']) ? e($user['email']) : ''; ?>" required>
</div>
<div class="field">
  <label>Password <?php echo isset($user) ? '(leave blank to keep)' : ''; ?></label>
  <input name="password" type="password" value="">
</div>
<button class="btn btn-primary">Save</button>
