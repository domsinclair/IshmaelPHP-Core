<?php
declare(strict_types=1);

namespace Ishmael\Core\Authz;

use RuntimeException;

/**
 * AuthorizationException represents a 403 Forbidden decision.
 */
final class AuthorizationException extends RuntimeException
{
    private string $ability;
    /** @var mixed */
    private $resource;

    /**
     * @param string $ability Ability that was checked
     * @param mixed $resource Optional resource/context
     * @param string $message Optional custom message
     */
    public function __construct(string $ability, mixed $resource = null, string $message = 'Forbidden')
    {
        parent::__construct($message, 403);
        $this->ability = $ability;
        $this->resource = $resource;
    }

    public function getAbility(): string
    {
        return $this->ability;
    }

    /** @return mixed */
    public function getResource(): mixed
    {
        return $this->resource;
    }
}
