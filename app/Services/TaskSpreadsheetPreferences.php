<?php

namespace App\Services;

use App\Enums\SpreadsheetDensity;
use App\Enums\SpreadsheetLayout;
use App\Enums\SpreadsheetRangeKey;
use App\Enums\SpreadsheetViewKey;
use App\Models\Church;
use App\Models\User;

class TaskSpreadsheetPreferences
{
    /**
     * @return array{
     *     view: string,
     *     density: string,
     *     layout: string,
     *     range: string,
     *     from: ?string,
     *     until: ?string
     * }
     */
    public function defaults(): array
    {
        return [
            'view' => SpreadsheetViewKey::AllStaffing->value,
            'density' => SpreadsheetDensity::Comfortable->value,
            'layout' => SpreadsheetLayout::Auto->value,
            'range' => SpreadsheetRangeKey::NextSixWeeks->value,
            'from' => null,
            'until' => null,
        ];
    }

    /**
     * @return array{
     *     view: string,
     *     density: string,
     *     layout: string,
     *     range: string,
     *     from: ?string,
     *     until: ?string
     * }
     */
    public function for(User $user, Church $church): array
    {
        $stored = $user->spreadsheet_preferences ?? [];
        $churchPrefs = is_array($stored[(string) $church->id] ?? null)
            ? $stored[(string) $church->id]
            : [];

        $merged = array_merge($this->defaults(), $churchPrefs);

        return [
            'view' => SpreadsheetViewKey::tryFrom((string) $merged['view'])?->value
                ?? SpreadsheetViewKey::AllStaffing->value,
            'density' => SpreadsheetDensity::tryFrom((string) $merged['density'])?->value
                ?? SpreadsheetDensity::Comfortable->value,
            'layout' => SpreadsheetLayout::tryFrom((string) $merged['layout'])?->value
                ?? SpreadsheetLayout::Auto->value,
            'range' => SpreadsheetRangeKey::tryFrom((string) $merged['range'])?->value
                ?? SpreadsheetRangeKey::NextSixWeeks->value,
            'from' => filled($merged['from'] ?? null) ? (string) $merged['from'] : null,
            'until' => filled($merged['until'] ?? null) ? (string) $merged['until'] : null,
        ];
    }

    /**
     * @param  array{
     *     view?: string,
     *     density?: string,
     *     layout?: string,
     *     range?: string,
     *     from?: ?string,
     *     until?: ?string
     * }  $prefs
     */
    public function remember(User $user, Church $church, array $prefs): void
    {
        $current = $this->for($user, $church);
        $next = array_merge($current, $prefs);
        $all = $user->spreadsheet_preferences ?? [];
        $all[(string) $church->id] = [
            'view' => SpreadsheetViewKey::tryFrom((string) $next['view'])?->value
                ?? SpreadsheetViewKey::AllStaffing->value,
            'density' => SpreadsheetDensity::tryFrom((string) $next['density'])?->value
                ?? SpreadsheetDensity::Comfortable->value,
            'layout' => SpreadsheetLayout::tryFrom((string) $next['layout'])?->value
                ?? SpreadsheetLayout::Auto->value,
            'range' => SpreadsheetRangeKey::tryFrom((string) $next['range'])?->value
                ?? SpreadsheetRangeKey::NextSixWeeks->value,
            'from' => filled($next['from'] ?? null) ? (string) $next['from'] : null,
            'until' => filled($next['until'] ?? null) ? (string) $next['until'] : null,
        ];

        $user->forceFill(['spreadsheet_preferences' => $all])->save();
    }
}
