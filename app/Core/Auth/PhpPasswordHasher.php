<?php

declare(strict_types=1);

namespace Ishmael\Core\Auth;

/**
 * PhpPasswordHasher implements HasherInterface using PHP's password_* APIs.
 * Algorithm and cost are read from config('auth.passwords').
 */
final class PhpPasswordHasher implements HasherInterface
{
    /** @var array<string,mixed> */
    private array $options;
    public function __construct()
    {
        /** @var array<string,mixed> $cfg */
        $cfg = (array) (\config('auth.passwords') ?? []);
        $algo = (string)($cfg['algo'] ?? 'bcrypt');
        $this->options = [
            'algo' => $algo,
            'cost' => isset($cfg['cost']) ? (int)$cfg['cost'] : 12,
            'memory_cost' => isset($cfg['memory_cost']) ? (int)$cfg['memory_cost'] : PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
            'time_cost' => isset($cfg['time_cost']) ? (int)$cfg['time_cost'] : PASSWORD_ARGON2_DEFAULT_TIME_COST,
            'threads' => isset($cfg['threads']) ? (int)$cfg['threads'] : PASSWORD_ARGON2_DEFAULT_THREADS,
        ];
    }

    public function hash(string $plain): string
    {
        [$algo, $opts] = $this->resolveAlgo();
        $hash = password_hash($plain, $algo, $opts);
        return $hash;
    }

    public function verify(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        [$algo, $opts] = $this->resolveAlgo();
        return password_needs_rehash($hash, $algo, $opts);
    }

    /**
     * @return array{0:int|string,1:array<string,int>}
     */
    private function resolveAlgo(): array
    {
        $algoName = strtolower((string)$this->options['algo']);
        switch ($algoName) {
            case 'argon2id':
                $algo = PASSWORD_ARGON2ID;
                $opts = [
                    'memory_cost' => (int)$this->options['memory_cost'],
                    'time_cost' => (int)$this->options['time_cost'],
                    'threads' => (int)$this->options['threads'],
                ];

                break;
            case 'argon2i':
                $algo = PASSWORD_ARGON2I;
                $opts = [
                'memory_cost' => (int)$this->options['memory_cost'],
                'time_cost' => (int)$this->options['time_cost'],
                'threads' => (int)$this->options['threads'],
                ];

                break;
            case 'bcrypt':
            default:
                $algo = PASSWORD_BCRYPT;
                $opts = ['cost' => (int)$this->options['cost']];

                break;
        }
        return [$algo, $opts];
    }
}
