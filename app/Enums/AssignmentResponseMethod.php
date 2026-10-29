<?php

namespace App\Enums;

enum AssignmentResponseMethod: string
{
    case Replied = 'replied';
    case Verbal = 'verbal';

    public function label(): string
    {
        return match ($this) {
            self::Replied => __('Replied by the person'),
            self::Verbal => __('Accepted verbally'),
        };
    }
}
