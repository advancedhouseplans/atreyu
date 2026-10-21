<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Backlog = 'backlog';
    case InProgress = 'in_progress';
    case Review = 'review';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Backlog => 'Backlog',
            self::InProgress => 'In progress',
            self::Review => 'Review',
            self::Done => 'Done',
        };
    }
}
