<?php

namespace App\Enums;

enum TaskDifficulty: int
{
    case One = 1;
    case Two = 2;
    case Three = 3;
    case Four = 4;
    case Five = 5;

    public function label(): string
    {
        return match ($this) {
            self::One => '1 · Trivial',
            self::Two => '2 · Small',
            self::Three => '3 · Moderate',
            self::Four => '4 · Complex',
            self::Five => '5 · Epic',
        };
    }
}
