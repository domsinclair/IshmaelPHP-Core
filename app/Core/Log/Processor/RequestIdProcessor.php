<?php
declare(strict_types=1);

namespace Ishmael\Core\Log\Processor;

/**
 * Adds request_id from global context if available (set by RequestIdMiddleware).
 */
final class RequestIdProcessor implements ProcessorInterface
{
    /**
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    public function __invoke(array $context): array
    {
        $rid = null;
        if (function_exists('app')) {
            $rid = app('request_id');
        }
        if (is_string($rid) && $rid !== '') {
            return ['request_id' => $rid];
        }
        return [];
    }
}
