<?php

declare(strict_types=1);

namespace Ishmael\Core\Log\Monolog;

use Monolog\Formatter\JsonFormatter;

/**
 * Preset for JSON Lines output using Monolog's JsonFormatter
 * with appendNewline=true so each record ends with a newline.
 */
final class JsonLinesFormatter extends JsonFormatter
{
    public function __construct()
    {
        // $appendNewline = true, $ignoreEmptyContextAndExtra = false, $includeStacktraces = false
        parent::__construct(self::BATCH_MODE_JSON, true, false, false);
    }
}
