<?php /** @var Ishmael\Core\ViewSections $sections */ ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?php echo htmlspecialchars($title ?? 'Contacts', ENT_QUOTES, 'UTF-8'); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 2rem; }
    .container { max-width: 920px; margin: 0 auto; }
    .flash { padding: .75rem 1rem; background: #eef6ff; border: 1px solid #b6e0fe; color: #1e3a8a; margin-bottom: 1rem; }
    .btn { display:inline-block; padding:.5rem .75rem; border:1px solid #ccc; background:#f8f9fa; text-decoration:none; color:#111; border-radius: .25rem }
    .btn-primary { background:#2563eb; color:#fff; border-color:#1d4ed8 }
    .field { margin-bottom: .75rem; }
    label { display:block; font-weight:600; margin-bottom:.25rem }
    input, textarea { width:100%; padding:.5rem; border:1px solid #ccc; border-radius:.25rem }
    table { width:100%; border-collapse: collapse; }
    th, td { border-bottom:1px solid #eee; padding:.5rem; text-align:left }
  </style>
</head>
<body>
  <div class="container">
    <?php echo $sections->yield('content'); ?>
  </div>
</body>
</html>
