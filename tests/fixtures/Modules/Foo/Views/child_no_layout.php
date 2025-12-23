<?php

namespace Ishmael\Tests; /** @var string|null $who */ ?>
<p>Hello <?php

namespace Ishmael\Tests; echo isset($who) ? htmlspecialchars((string)$who, ENT_QUOTES, 'UTF-8') : 'World'; ?></p>


