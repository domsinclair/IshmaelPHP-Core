<?php

declare(strict_types=1);

namespace Ishmael\Core\Validation;

use RuntimeException;

/**
 * ValidationException carries a structured error bag and snapshot of the old input.
 */
final class ValidationException extends RuntimeException
{
    /** @var array<string, string[]> */
    private array $messages;
/** @var array<string, string[]> */
    private array $codes;
/** @var array<string, mixed> */
    private array $old;
/**
     * @param array<string,string[]> $messages Field => list of human messages
     * @param array<string,string[]> $codes Field => list of message keys (i18n-ready)
     * @param array<string,mixed> $old Snapshot of previous input
     */
    public function __construct(array $messages, array $codes = [], array $old = [])
    {
        parent::__construct('Validation failed', 422);
        $this->messages = $messages;
        $this->codes = $codes;
        $this->old = $old;
    }

    /** @return array<string,string[]> */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /** @return array<string,string[]> */
    public function getCodes(): array
    {
        return $this->codes;
    }

    /** @return array<string,mixed> */
    public function getOld(): array
    {
        return $this->old;
    }
}
