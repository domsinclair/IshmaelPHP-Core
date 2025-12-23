<?php

declare(strict_types=1);

namespace Ishmael\Core\Log\Processor;

/**
 * A simple processor interface to enrich log context globally.
 * Implementations return an associative array of context additions.
 */
interface ProcessorInterface
{
    /**
     * @param array<string,mixed> $context
     * @return array<string,mixed> Context additions/overrides
     */
    public function __invoke(array $context): array;
}
