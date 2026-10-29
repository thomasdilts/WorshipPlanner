<?php

namespace App\Enums;

enum FieldUsage: string
{
    case NotUsed = 'not_used';
    case Optional = 'optional';
    case Required = 'required';

    public function label(): string
    {
        return match ($this) {
            self::NotUsed => __('Not used'),
            self::Optional => __('Optional'),
            self::Required => __('Required'),
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

    public function isUsed(): bool
    {
        return $this !== self::NotUsed;
    }

    public function isRequired(): bool
    {
        return $this === self::Required;
    }
}
