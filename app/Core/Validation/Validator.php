<?php
declare(strict_types=1);

namespace Ishmael\Core\Validation;

use Ishmael\Core\Http\Request;

/**
 * Validator provides minimal validation with common rules and i18n-ready codes.
 * Supported rules:
 * - required
 * - string
 * - int
 * - email
 * - min:<n>
 * - max:<n>
 * - in:a,b,c
 * - regex:/pattern/
 */
final class Validator
{
    /** @var array<string,string[]> */
    private array $messages = [];
    /** @var array<string,string[]> */
    private array $codes = [];

    /**
     * Validate given data against rules and return sanitized data or throw.
     *
     * @param array<string,mixed> $data
     * @param array<string,string|array<int,string>> $rules
     * @return array<string,mixed>
     * @throws ValidationException
     */
    public function validate(array $data, array $rules): array
    {
        $clean = [];
        foreach ($rules as $field => $ruleSpec) {
            $rulesArr = is_array($ruleSpec) ? $ruleSpec : explode('|', (string)$ruleSpec);
            $value = $data[$field] ?? null;
            $present = array_key_exists($field, $data);

            // Parse rules into name=>param
            $parsed = [];
            foreach ($rulesArr as $r) {
                $r = trim((string)$r);
                if ($r === '') { continue; }
                $parts = explode(':', $r, 2);
                $name = strtolower($parts[0]);
                $param = $parts[1] ?? null;
                $parsed[] = [$name, $param];
            }

            // required first
            if ($this->hasRule($parsed, 'required')) {
                if (!$present || $this->isEmpty($value)) {
                    $this->addError($field, 'validation.required', '%s is required.');
                    continue;
                }
            } else {
                // not required; if not present or empty string, skip other rules
                if (!$present || $this->isEmpty($value)) {
                    continue;
                }
            }

            $current = $value;

            // type: string
            if ($this->hasRule($parsed, 'string')) {
                if (is_scalar($current)) {
                    $current = trim((string)$current);
                }
                if (!is_string($current)) {
                    $this->addError($field, 'validation.string', '%s must be a string.');
                    // Do not cast
                }
            }

            // type: int
            if ($this->hasRule($parsed, 'int')) {
                if (is_int($current)) {
                    // ok
                } elseif (is_string($current) && preg_match('/^-?\d+$/', $current) === 1) {
                    $current = (int)$current;
                } else {
                    $this->addError($field, 'validation.int', '%s must be an integer.');
                }
            }

            // email
            if ($this->hasRule($parsed, 'email')) {
                $str = is_string($current) ? $current : (is_scalar($current) ? (string)$current : '');
                if (!filter_var($str, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'validation.email', '%s must be a valid email address.');
                } else {
                    $current = $str;
                }
            }

            // min/max
            foreach ($parsed as [$name, $param]) {
                if ($name === 'min' && $param !== null) {
                    $n = (int)$param;
                    if (is_int($current)) {
                        if ($current < $n) { $this->addError($field, 'validation.min', '%s must be at least ' . $n . '.'); }
                    } else {
                        $len = strlen((string)$current);
                        if ($len < $n) { $this->addError($field, 'validation.min', '%s must be at least ' . $n . ' characters.'); }
                    }
                }
                if ($name === 'max' && $param !== null) {
                    $n = (int)$param;
                    if (is_int($current)) {
                        if ($current > $n) { $this->addError($field, 'validation.max', '%s may not be greater than ' . $n . '.'); }
                    } else {
                        $len = strlen((string)$current);
                        if ($len > $n) { $this->addError($field, 'validation.max', '%s may not be greater than ' . $n . ' characters.'); }
                    }
                }
            }

            // in:list
            $inRule = $this->firstRule($parsed, 'in');
            if ($inRule !== null && $inRule[1] !== null) {
                $allowed = array_map('trim', explode(',', (string)$inRule[1]));
                if (!in_array((string)$current, $allowed, true)) {
                    $this->addError($field, 'validation.in', '%s must be one of: ' . implode(', ', $allowed) . '.');
                }
            }

            // regex:/.../
            $rxRule = $this->firstRule($parsed, 'regex');
            if ($rxRule !== null && $rxRule[1] !== null) {
                $pattern = (string)$rxRule[1];
                $str = is_string($current) ? $current : (is_scalar($current) ? (string)$current : '');
                if (@preg_match($pattern, '') === false || preg_match($pattern, $str) !== 1) {
                    $this->addError($field, 'validation.regex', '%s format is invalid.');
                }
            }

            // Assign sanitized value if no errors for field
            if (!isset($this->messages[$field])) {
                $clean[$field] = $current;
            }
        }

        if (!empty($this->messages)) {
            throw new ValidationException($this->messages, $this->codes, $data);
        }

        return $clean;
    }

    /**
     * Helper to validate the current request input (query overrides body).
     * @param array<string,string|array<int,string>> $rules
     * @return array<string,mixed>
     * @throws ValidationException
     */
    public function validateRequest(array $rules, ?Request $request = null): array
    {
        $req = $request ?? Request::fromGlobals();
        $data = array_merge($req->getParsedBody(), $req->getQueryParams());
        return $this->validate($data, $rules);
    }

    /** @param array<int,array{0:string,1:?string}> $parsed */
    private function hasRule(array $parsed, string $name): bool
    {
        foreach ($parsed as [$n, $_]) { if ($n === $name) { return true; } }
        return false;
    }

    /** @param array<int,array{0:string,1:?string}> $parsed */
    private function firstRule(array $parsed, string $name): ?array
    {
        foreach ($parsed as $r) { if ($r[0] === $name) { return $r; } }
        return null;
    }

    private function isEmpty(mixed $v): bool
    {
        if ($v === null) { return true; }
        if (is_string($v)) { return trim($v) === ''; }
        if (is_array($v)) { return count($v) === 0; }
        return false;
    }

    private function addError(string $field, string $code, string $messageTpl): void
    {
        $msg = sprintf($messageTpl, ucfirst(str_replace('_', ' ', $field)));
        $this->messages[$field][] = $msg;
        $this->codes[$field][] = $code;
    }
}
