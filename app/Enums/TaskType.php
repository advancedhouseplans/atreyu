<?php

namespace App\Enums;

enum TaskType: string
{
    case Bug = 'bug';
    case Build = 'build';

    public function label(): string
    {
        return match ($this) {
            self::Bug => 'Bug',
            self::Build => 'Build',
        };
    }
}
