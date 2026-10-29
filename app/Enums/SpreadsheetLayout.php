<?php

namespace App\Enums;

enum SpreadsheetLayout: string
{
    case Auto = 'auto';
    case Grid = 'grid';
    case List = 'list';

    public function label(): string
    {
        return match ($this) {
            self::Auto => __('Auto'),
            self::Grid => __('Full grid'),
            self::List => __('List'),
        };
    }
}
