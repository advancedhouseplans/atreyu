<?php

namespace BugCompanion\Support;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Stringable;

class Redactor
{
    public function value(mixed $value, ?string $key = null, int $depth = 0): mixed
    {
        if ($key !== null && $this->isSensitiveKey($key)) {
            return '[REDACTED]';
        }

        if ($depth >= 4) {
            return '[MAX DEPTH]';
        }

        if ($value instanceof Model) {
            return ['model' => $value::class, 'id' => $value->getKey()];
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if ($value instanceof Arrayable) {
            return $this->value($value->toArray(), $key, $depth + 1);
        }

        if (is_array($value)) {
            $redacted = [];
            foreach ($value as $itemKey => $itemValue) {
                $redacted[$itemKey] = $this->value($itemValue, (string) $itemKey, $depth + 1);
            }

            return $redacted;
        }

        if (is_object($value)) {
            return $value instanceof Stringable
                ? $this->truncate((string) $value)
                : ['object' => $value::class];
        }

        if (is_resource($value)) {
            return '[RESOURCE]';
        }

        return is_string($value) ? $this->truncate($value) : $value;
    }

    public function queryBinding(mixed $value): mixed
    {
        $mode = config('bug-companion.telemetry.query_bindings', 'identifiers');

        if ($mode === false) {
            return '[OMITTED]';
        }

        if (is_string($value) && $this->looksSecret($value)) {
            return '[REDACTED]';
        }

        if ($mode === true || $mode === 'all' || ! is_string($value)) {
            return $this->value($value);
        }

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value)
            || preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $value)) {
            return $value;
        }

        return '[STRING '.mb_strlen($value).' chars]';
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(str_replace(['-', '.'], '_', $key));

        return collect(config('bug-companion.telemetry.sensitive_keys', []))
            ->contains(fn (mixed $sensitive): bool => $normalized === strtolower((string) $sensitive)
                || str_ends_with($normalized, '_'.strtolower((string) $sensitive)));
    }

    private function looksSecret(string $value): bool
    {
        return str_starts_with($value, 'Bearer ')
            || preg_match('/-----BEGIN [A-Z ]+PRIVATE KEY-----/', $value) === 1
            || preg_match('/^(?:sk|pk|ghp|github_pat|xox[baprs])-[_A-Za-z0-9-]{16,}$/', $value) === 1;
    }

    private function truncate(string $value): string
    {
        return mb_substr($value, 0, (int) config('bug-companion.telemetry.max_string_length', 500));
    }
}
