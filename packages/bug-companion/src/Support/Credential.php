<?php

namespace BugCompanion\Support;

class Credential
{
    public function __construct(
        public readonly string $keyId,
        public readonly string $secret,
    ) {}

    public static function fromConfig(): ?self
    {
        $value = trim((string) config('bug-companion.key', ''));
        if ($value === '') {
            return null;
        }

        if (str_contains($value, '.')) {
            [$keyId, $secret] = explode('.', $value, 2);

            return $keyId !== '' && $secret !== '' ? new self($keyId, $secret) : null;
        }

        $legacyProject = trim((string) config('bug-companion.project', ''));

        return $legacyProject !== '' ? new self($legacyProject, $value) : null;
    }
}
