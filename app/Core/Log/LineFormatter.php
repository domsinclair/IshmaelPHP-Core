<?php
declare(strict_types=1);

namespace Ishmael\Core\Log;

/**
 * Simple human-readable formatter that renders a single log line like:
 * [2025-11-04T12:00:00+00:00] INFO: Message
 * Always terminates with a newline.
 */
final class LineFormatter implements FormatterInterface
{
    /**
     * @param array<string,mixed> $record Normalized record with keys: ts, lvl, msg, app, env, request_id, context
     */
    public function format(array $record): string
    {
        $ts = (string)($record['ts'] ?? '');
        $lvl = strtoupper((string)($record['lvl'] ?? ''));
        $msg = (string)($record['msg'] ?? '');
        return sprintf('[%s] %s: %s', $ts, $lvl, $msg) . "\n";
    }
}
