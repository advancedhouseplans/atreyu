<?php

namespace App\Enums;

enum Appearance: string
{
    case System = 'system';
    case Light = 'light';
    case Dark = 'dark';

    public function label(): string
    {
        return match ($this) {
            self::System => 'System',
            self::Light => 'Light',
            self::Dark => 'Dark',
        };
    }
}
