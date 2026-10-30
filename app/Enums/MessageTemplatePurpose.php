<?php

namespace App\Enums;

/**
 * Where a message template can be chosen: service messages, team messages, or neither (e.g. the
 * preferred-messaging confirmation, which is chosen in the SMS settings).
 */
enum MessageTemplatePurpose: string
{
    case Service = 'service';
    case Team = 'team';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Service => __('Service messages'),
            self::Team => __('Team messages'),
            self::Other => __('Other'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Service => __('Can be chosen when sending a message about a service.'),
            self::Team => __('Can be chosen when sending a message to a team.'),
            self::Other => __('Never offered when sending messages, e.g. the preferred messaging confirmation.'),
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

    /**
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->description()])
            ->all();
    }
}
