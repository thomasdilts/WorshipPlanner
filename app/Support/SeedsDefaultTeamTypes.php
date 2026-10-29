<?php

namespace App\Support;

use App\Models\Church;
use App\Models\TeamType;

final class SeedsDefaultTeamTypes
{
    /**
     * @var list<string>
     */
    public const DEFAULTS = [
        'Music team',
        'Prayer team',
        'Greeter team',
        'Tech team',
        'Kids team',
    ];

    public static function for(Church $church): void
    {
        foreach (self::DEFAULTS as $name) {
            TeamType::query()->firstOrCreate([
                'church_id' => $church->id,
                'name' => $name,
            ]);
        }
    }
}
