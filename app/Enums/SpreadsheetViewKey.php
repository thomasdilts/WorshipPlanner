<?php

namespace App\Enums;

enum SpreadsheetViewKey: string
{
    case AllStaffing = 'all_staffing';
    case NeedsAttention = 'needs_attention';
    case MyTeamNeeds = 'my_team_needs';

    public function label(): string
    {
        return match ($this) {
            self::AllStaffing => __('All staffing'),
            self::NeedsAttention => __('Needs attention'),
            self::MyTeamNeeds => __('My team\'s needs'),
        };
    }
}
