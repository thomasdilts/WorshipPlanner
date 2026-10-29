<?php

namespace App\Enums;

enum ExpectedUserRange: string
{
    case OneToTen = '1-10';
    case ElevenToThirty = '11-30';
    case ThirtyOneToFifty = '31-50';
    case FiftyOneToHundred = '51-100';
    case OverHundred = '100+';

    public function label(): string
    {
        return match ($this) {
            self::OneToTen => '1–10',
            self::ElevenToThirty => '11–30',
            self::ThirtyOneToFifty => '31–50',
            self::FiftyOneToHundred => '51–100',
            self::OverHundred => '100+',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
