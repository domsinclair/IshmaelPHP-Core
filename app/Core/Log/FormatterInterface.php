<?php

declare(strict_types=1);

namespace Ishmael\Core\Log;

/**
 * Formats a normalized log record into a string to be written by a channel.
 * The formatter must always return a newline-terminated string.
 */
interface FormatterInterface
{
    /**
     * @param array<string,mixed> $record Normalized record with keys: ts, lvl, msg, app, env, request_id, context
     */
    public function format(array $record): string;
}
