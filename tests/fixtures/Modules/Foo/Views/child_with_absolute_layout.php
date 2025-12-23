<?php

namespace Ishmael\Tests; /** @var Ishmael\Core\ViewSections $sections */ ?>
<?php

namespace Ishmael\Tests; /** @var string|null $who */ ?>
<?php

namespace Ishmael\Tests; $layoutFile = __DIR__ . DIRECTORY_SEPARATOR . 'layout.php'; ?>
<?php

namespace Ishmael\Tests; $sections->start('content'); ?>
<p>Hi <?php

namespace Ishmael\Tests; echo isset($who) ? htmlspecialchars((string)$who, ENT_QUOTES, 'UTF-8') : 'World'; ?></p>
<?php

namespace Ishmael\Tests; $sections->end(); ?>


