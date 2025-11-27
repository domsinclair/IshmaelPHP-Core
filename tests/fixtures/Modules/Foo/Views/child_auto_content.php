<?php /** Child view that declares a layout but does not define sections. */ ?>
<?php /** @var string|null $who */ ?>
<?php $layoutFile = 'layout'; ?>
<p>Hi <?php echo isset($who) ? htmlspecialchars((string)$who, ENT_QUOTES, 'UTF-8') : 'World'; ?></p>
