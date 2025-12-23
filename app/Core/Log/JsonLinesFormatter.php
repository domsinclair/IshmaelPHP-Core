<?php

declare(strict_types=1);

namespace Ishmael\Core\Log;

/**
 * Formatter that renders normalized records as JSON Lines (one JSON object per line).
 *
 * Keys are emitted in a stable order and the resulting string always ends with a newline.
 */
final class JsonLinesFormatter implements FormatterInterface
{
    /** @var int JSON encoding flags applied to json_encode */
    private int $jsonFlags;
/**
     * @param int $jsonFlags json_encode flags (defaults to UNESCAPED_SLASHES | UNESCAPED_UNICODE)
     */
    public function __construct(int $jsonFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    {
        $this->jsonFlags = $jsonFlags;
    }

    /**
     * @param array<string,mixed> $record Normalized record with keys: ts, lvl, msg, app, env, request_id, context
     */
    public function format(array $record): string
    {
        // Keep stable key order
        $ordered = [
            'ts' => $record['ts'] ?? null,
            'lvl' => $record['lvl'] ?? null,
            'msg' => $record['msg'] ?? null,
            'app' => $record['app'] ?? null,
            'env' => $record['env'] ?? null,
            'request_id' => $record['request_id'] ?? null,
            'context' => $record['context'] ?? [],
        ];
        return json_encode($ordered, $this->jsonFlags) . "\n";
    }
}
