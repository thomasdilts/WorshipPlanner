<?php

namespace App\Filament\Tables;

use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class DatePeriodFilter
{
    public static function make(string $dateColumn, ?string $relationship = null): SelectFilter
    {
        return SelectFilter::make('period')
            ->label(__('Show dates'))
            ->options([
                'upcoming' => __('Current & upcoming'),
                'past' => __('Past dates'),
                'all' => __('All dates'),
            ])
            ->default('upcoming')
            ->selectablePlaceholder(false)
            ->query(function (Builder $query, array $data) use ($dateColumn, $relationship): Builder {
                $period = $data['value'] ?? 'upcoming';

                if ($period === 'all') {
                    return $query;
                }

                $applyPeriod = fn (Builder $dates): Builder => $dates->whereDate($dateColumn, $period === 'past' ? '<' : '>=', today());

                return $relationship === null ? $applyPeriod($query) : $query->whereHas($relationship, $applyPeriod);
            });
    }
}
