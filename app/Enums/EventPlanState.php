<?php

namespace App\Enums;

/**
 * Plan publication markers — independent of individual assignment response state.
 */
enum EventPlanState: string
{
    case Draft = 'draft';
    case Shared = 'shared';
    case ChangedSinceShared = 'changed_since_shared';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft plan'),
            self::Shared => __('Shared'),
            self::ChangedSinceShared => __('Changed since shared'),
        };
    }
}
