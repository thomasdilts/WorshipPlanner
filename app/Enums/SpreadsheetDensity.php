<?php

namespace App\Enums;

enum SpreadsheetDensity: string
{
    case Compact = 'compact';
    case Comfortable = 'comfortable';

    public function label(): string
    {
        return match ($this) {
            self::Compact => __('Compact'),
            self::Comfortable => __('Comfortable'),
        };
    }
}
