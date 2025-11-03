<?php
declare(strict_types=1);

namespace Ishmael\Core\Log;

final class JsonLinesFormatter implements FormatterInterface
{
    /** @var int */
    private int $jsonFlags;

    public function __construct(int $jsonFlags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    {
        $this->jsonFlags = $jsonFlags;
    }

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
