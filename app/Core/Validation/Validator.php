<?php

declare(strict_types=1);

namespace Ishmael\Core\Validation;

use Ishmael\Core\Http\Request;
use Ishmael\Core\Http\UploadedFile;

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
                if ($r === '') {
                    continue;
                }
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
                        if ($current < $n) {
                            $this->addError($field, 'validation.min', '%s must be at least ' . $n . '.');
                        }
                    } elseif ($current instanceof UploadedFile) {
                        // handled by separate logic or skipped here
                    } else {
                        $len = strlen((string)$current);
                        if ($len < $n) {
                            $this->addError($field, 'validation.min', '%s must be at least ' . $n . ' characters.');
                        }
                    }
                }
                if ($name === 'max' && $param !== null) {
                    $n = (int)$param;
                    if (is_int($current)) {
                        if ($current > $n) {
                            $this->addError($field, 'validation.max', '%s may not be greater than ' . $n . '.');
                        }
                    } elseif ($current instanceof UploadedFile) {
                        // handled by separate logic or skipped here
                    } else {
                        $len = strlen((string)$current);
                        if ($len > $n) {
                            $this->addError($field, 'validation.max', '%s may not be greater than ' . $n . ' characters.');
                        }
                    }
                }
            }

            // in:list
            $inRule = $this->firstRule($parsed, 'in');
            if ($inRule !== null && $inRule[1] !== null) {
                $allowed = array_map('trim', explode(',', (string)$inRule[1]));
                $str = $current instanceof UploadedFile ? $current->getClientOriginalName() : (string)$current;
                if (!in_array($str, $allowed, true)) {
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

            // file
            if ($this->hasRule($parsed, 'file')) {
                if (!($current instanceof UploadedFile) || !$current->isValid()) {
                    $this->addError($field, 'validation.file', '%s must be a valid uploaded file.');
                }
            }

            // image
            if ($this->hasRule($parsed, 'image')) {
                if (!($current instanceof UploadedFile) || !$current->isValid()) {
                    $this->addError($field, 'validation.image', '%s must be an image.');
                } else {
                    $mime = $current->getClientMimeType();
                    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array($mime, $allowed, true)) {
                        $this->addError($field, 'validation.image', '%s must be an image (jpg, png, gif, webp).');
                    }
                }
            }

            // mimes:jpg,png,pdf
            $mimesRule = $this->firstRule($parsed, 'mimes');
            if ($mimesRule !== null && $mimesRule[1] !== null) {
                if (!($current instanceof UploadedFile) || !$current->isValid()) {
                    $this->addError($field, 'validation.mimes', '%s must be a file of type: ' . $mimesRule[1] . '.');
                } else {
                    $allowedExts = array_map('strtolower', array_map('trim', explode(',', (string)$mimesRule[1])));
                    $filename = $current->getClientOriginalName();
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedExts, true)) {
                        $this->addError($field, 'validation.mimes', '%s must be a file of type: ' . $mimesRule[1] . '.');
                    }
                }
            }

            // max:2048 (for files, this is KB)
            $maxRule = $this->firstRule($parsed, 'max');
            if ($maxRule !== null && $maxRule[1] !== null) {
                if ($current instanceof UploadedFile) {
                    $maxKb = (int)$maxRule[1];
                    if ($current->getSize() > $maxKb * 1024) {
                        $this->addError($field, 'validation.max_file', '%s may not be greater than ' . $maxKb . ' kilobytes.');
                    }
                }
            }

            // dimensions:min_width=100,min_height=100
            $dimRule = $this->firstRule($parsed, 'dimensions');
            if ($dimRule !== null && $dimRule[1] !== null) {
                if (!($current instanceof UploadedFile) || !$current->isValid()) {
                    $this->addError($field, 'validation.dimensions', '%s must be an image with valid dimensions.');
                } else {
                    $params = [];
                    foreach (explode(',', (string)$dimRule[1]) as $pair) {
                        $kv = explode('=', $pair, 2);
                        if (count($kv) === 2) {
                            $params[trim($kv[0])] = (int)trim($kv[1]);
                        }
                    }
                    $imgSize = @getimagesize($current->getRealPath());
                    if ($imgSize === false) {
                        $this->addError($field, 'validation.dimensions', '%s must be a valid image.');
                    } else {
                        [$width, $height] = $imgSize;
                        if (isset($params['min_width']) && $width < $params['min_width']) {
                            $this->addError($field, 'validation.dimensions.min_width', '%s width must be at least ' . $params['min_width'] . 'px.');
                        }
                        if (isset($params['min_height']) && $height < $params['min_height']) {
                            $this->addError($field, 'validation.dimensions.min_height', '%s height must be at least ' . $params['min_height'] . 'px.');
                        }
                        if (isset($params['max_width']) && $width > $params['max_width']) {
                            $this->addError($field, 'validation.dimensions.max_width', '%s width may not be greater than ' . $params['max_width'] . 'px.');
                        }
                        if (isset($params['max_height']) && $height > $params['max_height']) {
                            $this->addError($field, 'validation.dimensions.max_height', '%s height may not be greater than ' . $params['max_height'] . 'px.');
                        }
                        if (isset($params['width']) && $width !== $params['width']) {
                            $this->addError($field, 'validation.dimensions.width', '%s width must be ' . $params['width'] . 'px.');
                        }
                        if (isset($params['height']) && $height !== $params['height']) {
                            $this->addError($field, 'validation.dimensions.height', '%s height must be ' . $params['height'] . 'px.');
                        }
                    }
                }
            }

            // Assign sanitized value if no errors for field
            if (!isset($this->messages[$field])) {
                $clean[$field] = $current;
            }
        }

        if (!empty($this->messages)) {
            throw new ValidationException($this->messages, $this->codes);
        }

        return $clean;
    }

    public function getErrors(): array
    {
        return $this->messages;
    }

    public function getCodes(): array
    {
        return $this->codes;
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
        foreach ($parsed as [$n, $_]) {
            if ($n === $name) {
                return true;
            }
        }
        return false;
    }

    /** @param array<int,array{0:string,1:?string}> $parsed */
    private function firstRule(array $parsed, string $name): ?array
    {
        foreach ($parsed as $r) {
            if ($r[0] === $name) {
                return $r;
            }
        }
        return null;
    }

    private function isEmpty(mixed $v): bool
    {
        if ($v === null) {
            return true;
        }
        if (is_string($v)) {
            return trim($v) === '';
        }
        if (is_array($v)) {
            return count($v) === 0;
        }
        return false;
    }

    private function addError(string $field, string $code, string $messageTpl): void
    {
        $msg = sprintf($messageTpl, ucfirst(str_replace('_', ' ', $field)));
        $this->messages[$field][] = $msg;
        $this->codes[$field][] = $code;
    }
}
