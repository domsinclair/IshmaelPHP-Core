<?php

namespace Ishmael\Tests; /** @var Ishmael\Core\ViewSections $sections */ ?>
<?php

namespace Ishmael\Tests; /** @var array<string,mixed> $data */ ?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title><?php

namespace Ishmael\Tests; echo htmlspecialchars($data['appName'] ?? 'App', ENT_QUOTES, 'UTF-8'); ?></title></head>
<body>
<header><?php

namespace Ishmael\Tests; echo htmlspecialchars($data['appName'] ?? 'App', ENT_QUOTES, 'UTF-8'); ?></header>
<main>
  <?php

namespace Ishmael\Tests; echo $sections->yield('content'); ?>
</main>
<footer>Footer</footer>
</body>
</html>


