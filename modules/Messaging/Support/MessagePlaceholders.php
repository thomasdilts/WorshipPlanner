<?php

namespace Modules\Messaging\Support;

use App\Models\Event;
use App\Models\EventTask;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Replaces {{ placeholder }} tokens in message subjects and bodies with per-recipient values.
 */
class MessagePlaceholders
{
    public const KEYS = ['name', 'first_name', 'task', 'event', 'date', 'time', 'church'];

    public const LINK_KEYS = ['accept_link', 'decline_link', 'accept_url', 'decline_url'];

    /** Replaced with the text the sender types when choosing a template that contains it. */
    public const CUSTOM_MESSAGE = 'custom_mess';

    /**
     * One-line hint listing every placeholder, for form helper texts.
     */
    public static function hint(): string
    {
        $tokens = collect([...self::KEYS, self::CUSTOM_MESSAGE, ...self::LINK_KEYS])
            ->map(fn (string $key): string => '{{'.$key.'}}')
            ->implode(', ');

        return __('Placeholders replaced per recipient: :placeholders', ['placeholders' => $tokens]);
    }

    /**
     * @param  Collection<int, EventTask>  $tasks
     * @return array<string, string>
     */
    public function valuesFor(User $recipient, ?Event $event, Collection $tasks): array
    {
        $church = $recipient->church;
        $name = (string) ($recipient->display_name ?: $recipient->name);
        $startsAt = $event?->starts_at?->timezone($church?->timezone ?: config('app.timezone'));

        return [
            'name' => $name,
            'first_name' => (string) strtok($name, ' '),
            'task' => $tasks->map(fn (EventTask $task): string => $task->displayName())->unique()->implode(', '),
            'event' => (string) ($event?->name ?? ''),
            'date' => (string) $startsAt?->translatedFormat('l j F Y'),
            'time' => (string) $startsAt?->format('H:i'),
            'church' => (string) ($church?->name ?? ''),
        ];
    }

    /**
     * Whether any of the texts contains {{custom_mess}}.
     */
    public static function usesCustomMessage(?string ...$texts): bool
    {
        foreach ($texts as $text) {
            if (preg_match('/\{\{\s*'.self::CUSTOM_MESSAGE.'\s*\}\}/', (string) $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the text places its own accept / decline links (then no automatic footer is added).
     */
    public static function usesResponseLinks(?string $text): bool
    {
        return (bool) preg_match('/\{\{\s*(?:'.implode('|', self::LINK_KEYS).')\s*\}\}/', (string) $text);
    }

    /**
     * Unknown placeholders are left untouched so typos stay visible.
     *
     * @param  array<string, string>  $values
     * @param  array<string, string>  $htmlValues  Ready-made HTML used as-is when $escapeHtml is true.
     */
    public function render(string $text, array $values, bool $escapeHtml, array $htmlValues = []): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([a-z_]+)\s*\}\}/',
            function (array $match) use ($values, $escapeHtml, $htmlValues): string {
                $key = $match[1];

                if ($escapeHtml && array_key_exists($key, $htmlValues)) {
                    return $htmlValues[$key];
                }

                if (! array_key_exists($key, $values)) {
                    return $match[0];
                }

                return $escapeHtml ? e($values[$key]) : $values[$key];
            },
            $text,
        );
    }
}
