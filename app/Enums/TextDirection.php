<?php

namespace App\Enums;

enum TextDirection: string
{
    case Ltr = 'ltr';
    case Rtl = 'rtl';

    public function label(): string
    {
        return match ($this) {
            self::Ltr => __('Left to right'),
            self::Rtl => __('Right to left'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
