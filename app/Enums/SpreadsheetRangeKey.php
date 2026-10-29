<?php

namespace App\Enums;

use Illuminate\Support\Carbon;

enum SpreadsheetRangeKey: string
{
    case NextSixWeeks = 'next_six_weeks';
    case ThisMonth = 'this_month';
    case NextMonth = 'next_month';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::NextSixWeeks => __('Next 6 weeks'),
            self::ThisMonth => __('This month'),
            self::NextMonth => __('Next month'),
            self::Custom => __('Custom range'),
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon|null}
     */
    public function bounds(?string $from = null, ?string $until = null): array
    {
        $today = now()->startOfDay();

        return match ($this) {
            self::NextSixWeeks => [$today, $today->copy()->addWeeks(6)->endOfDay()],
            self::ThisMonth => [$today, $today->copy()->endOfMonth()->endOfDay()],
            self::NextMonth => [
                $today->copy()->addMonthNoOverflow()->startOfMonth(),
                $today->copy()->addMonthNoOverflow()->endOfMonth()->endOfDay(),
            ],
            self::Custom => [
                Carbon::parse($from ?? $today)->startOfDay(),
                filled($until) ? Carbon::parse($until)->endOfDay() : null,
            ],
        };
    }
}
