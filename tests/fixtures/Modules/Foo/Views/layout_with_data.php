<?php /** @var Ishmael\Core\ViewSections $sections */ ?>
<?php /** @var array<string,mixed> $data */ ?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title><?php echo htmlspecialchars($data['appName'] ?? 'App', ENT_QUOTES, 'UTF-8'); ?></title></head>
<body>
<header><?php echo htmlspecialchars($data['appName'] ?? 'App', ENT_QUOTES, 'UTF-8'); ?></header>
<main>
  <?php echo $sections->yield('content'); ?>
</main>
<footer>Footer</footer>
</body>
</html>
