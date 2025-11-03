<?php
declare(strict_types=1);

namespace Ishmael\Core\Log;

use DateTimeImmutable;
use Psr\Log\LogLevel;

/**
 * Shared PSR-3 helpers: thresholding, interpolation, and record normalization.
 */
trait BaseLoggerTrait
{
    /** @var array<string,int> */
    private static array $LEVEL_MAP = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT     => 1,
        LogLevel::CRITICAL  => 2,
        LogLevel::ERROR     => 3,
        LogLevel::WARNING   => 4,
        LogLevel::NOTICE    => 5,
        LogLevel::INFO      => 6,
        LogLevel::DEBUG     => 7,
    ];

    private string $minLevel = LogLevel::DEBUG;

    public function setMinLevel(string $level): void
    {
        $level = strtolower($level);
        if (isset(self::$LEVEL_MAP[$level])) {
            $this->minLevel = $level;
        }
    }

    private function shouldLog(string $level): bool
    {
        $lvl = strtolower($level);
        return isset(self::$LEVEL_MAP[$lvl]) && self::$LEVEL_MAP[$lvl] <= self::$LEVEL_MAP[$this->minLevel];
    }

    /** @param array<string,mixed> $context */
    private function interpolate(string $message, array $context): string
    {
        if (strpos($message, '{') === false) {
            return $message;
        }
        $replacements = [];
        foreach ($context as $key => $val) {
            if (is_null($val) || is_scalar($val) || (is_object($val) && method_exists($val, '__toString'))) {
                $replacements['{' . $key . '}'] = (string)$val;
            } elseif (is_object($val)) {
                $replacements['{' . $key . '}'] = '[object ' . get_class($val) . ']';
            } else {
                $replacements['{' . $key . '}'] = json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }
        return strtr($message, $replacements);
    }

    /** @param array<string,mixed> $context */
    private function redactContext(array $context): array
    {
        $sensitive = ['password','pwd','token','secret','api_key','apikey','authorization','auth','access_token','refresh_token'];
        $lowerKeys = array_fill_keys($sensitive, true);
        $walker = function ($value) use (&$walker, $lowerKeys) {
            if (is_array($value)) {
                $out = [];
                foreach ($value as $k => $v) {
                    $lk = is_string($k) ? strtolower($k) : $k;
                    if (is_string($lk) && isset($lowerKeys[$lk])) {
                        $out[$k] = 'REDACTED';
                    } else {
                        $out[$k] = $walker($v);
                    }
                }
                return $out;
            }
            return $value;
        };
        return $walker($context);
    }

    /**
     * Build a normalized record for formatting.
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    private function buildRecord(string $level, string $message, array $context): array
    {
        $msg = $this->interpolate($message, $context);
        $ctx = $this->redactContext($context);
        $ts = (new DateTimeImmutable())->format('c'); // ISO8601 with timezone
        $app = function_exists('env') ? (string)env('APP_NAME', 'Ishmael') : 'Ishmael';
        $envName = function_exists('env') ? (string)env('APP_ENV', 'development') : 'development';
        $requestId = $ctx['request_id'] ?? null;
        return [
            'ts' => $ts,
            'lvl' => strtolower($level),
            'msg' => $msg,
            'app' => $app,
            'env' => $envName,
            'request_id' => $requestId,
            'context' => $ctx,
        ];
    }
}
