<?php

namespace App\Enums;

enum Theme: string
{
    case Light = 'light';
    case Cupcake = 'cupcake';
    case Bumblebee = 'bumblebee';
    case Emerald = 'emerald';
    case Corporate = 'corporate';
    case Retro = 'retro';
    case Nord = 'nord';
    case Winter = 'winter';
    case Dark = 'dark';
    case Black = 'black';
    case Synthwave = 'synthwave';
    case Halloween = 'halloween';
    case Forest = 'forest';
    case Luxury = 'luxury';
    case Night = 'night';
    case Business = 'business';

    public function label(): string
    {
        return match ($this) {
            self::Synthwave => 'Synthwave',
            default => ucfirst($this->value),
        };
    }

    /** @return list<self> */
    public static function lightThemes(): array
    {
        return [
            self::Light,
            self::Cupcake,
            self::Bumblebee,
            self::Emerald,
            self::Corporate,
            self::Retro,
            self::Nord,
            self::Winter,
        ];
    }

    /** @return list<self> */
    public static function darkThemes(): array
    {
        return [
            self::Dark,
            self::Black,
            self::Synthwave,
            self::Halloween,
            self::Forest,
            self::Luxury,
            self::Night,
            self::Business,
        ];
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
