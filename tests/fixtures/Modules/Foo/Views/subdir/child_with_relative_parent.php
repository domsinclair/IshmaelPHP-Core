<?php $layoutFile = '../layout'; ?>
<?php /** @var Ishmael\Core\ViewSections $sections */ ?>
<?php /** @var string|null $who */ ?>
<?php $sections->start('content'); ?>
<p>Hi <?php echo isset($who) ? htmlspecialchars((string)$who, ENT_QUOTES, 'UTF-8') : 'World'; ?></p>
<?php $sections->end(); ?>
