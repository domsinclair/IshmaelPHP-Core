<div class="field">
  <label for="firstName">First name</label>
  <input id="firstName" name="firstName" value="<?php echo htmlspecialchars((string)($item['firstName'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
</div>
<div class="field">
  <label for="lastName">Last name</label>
  <input id="lastName" name="lastName" value="<?php echo htmlspecialchars((string)($item['lastName'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
</div>
<div class="field">
  <label for="email">Email</label>
  <input id="email" type="email" name="email" value="<?php echo htmlspecialchars((string)($item['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
</div>
<div class="field">
  <label for="phone">Phone</label>
  <input id="phone" name="phone" value="<?php echo htmlspecialchars((string)($item['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
</div>
<div class="field">
  <label for="notes">Notes</label>
  <textarea id="notes" name="notes" rows="4"><?php echo htmlspecialchars((string)($item['notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
</div>
<p>
  <button class="btn btn-primary" type="submit">Save</button>
  <a class="btn" href="<?php echo \Ishmael\Core\Router::url('contacts.index'); ?>">Cancel</a>
</p>
