<?php

namespace Ishmael\Tests; /** Child view that declares a layout but does not define sections. */ ?>
<?php

namespace Ishmael\Tests; /** @var string|null $who */ ?>
<?php

namespace Ishmael\Tests; $layoutFile = 'layout'; ?>
<p>Hi <?php

namespace Ishmael\Tests; echo isset($who) ? htmlspecialchars((string)$who, ENT_QUOTES, 'UTF-8') : 'World'; ?></p>


