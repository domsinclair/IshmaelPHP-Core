<?php /** @var string|null $who */ ?>
<p>Hello <?php echo isset($who) ? htmlspecialchars((string)$who, ENT_QUOTES, 'UTF-8') : 'World'; ?></p>
