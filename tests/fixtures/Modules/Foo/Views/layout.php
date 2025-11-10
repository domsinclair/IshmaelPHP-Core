<?php /** @var Ishmael\Core\ViewSections $sections */ ?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Test Layout</title></head>
<body>
<header>Header</header>
<main>
  <?php echo $sections->yield('content'); ?>
</main>
<footer>Footer</footer>
</body>
</html>
