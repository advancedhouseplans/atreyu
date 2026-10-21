<?php

namespace App\Enums;

enum Accent: string
{
    case Iris = 'iris';
    case Blue = 'blue';
    case Teal = 'teal';
    case Green = 'green';
    case Amber = 'amber';
    case Rose = 'rose';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** The accent's base hex, matching vendor/ahp/obsidian/resources/css/obsidian.css. */
    public function swatch(): string
    {
        return match ($this) {
            self::Iris => '#6A5AF0',
            self::Blue => '#2563EB',
            self::Teal => '#14B8A6',
            self::Green => '#22C55E',
            self::Amber => '#F59E0B',
            self::Rose => '#E11D48',
        };
    }
}
