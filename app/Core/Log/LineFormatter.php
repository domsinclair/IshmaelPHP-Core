<?php
declare(strict_types=1);

namespace Ishmael\Core\Log;

final class LineFormatter implements FormatterInterface
{
    public function format(array $record): string
    {
        $ts = (string)($record['ts'] ?? '');
        $lvl = strtoupper((string)($record['lvl'] ?? ''));
        $msg = (string)($record['msg'] ?? '');
        return sprintf('[%s] %s: %s', $ts, $lvl, $msg) . "\n";
    }
}
