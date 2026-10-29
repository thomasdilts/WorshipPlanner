<?php

namespace App\Support;

class AnnouncementPoints
{
    public static function appliesTo(?string $name, ?string $templateName = null): bool
    {
        foreach ([$name, $templateName] as $candidate) {
            if (in_array(mb_strtolower(trim($candidate ?? '')), ['pålysningar', 'palysningar', 'announcements', 'إعلانات', 'الإعلانات'], true)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<array{text: string}> */
    public static function fromDescription(?string $description): array
    {
        $lines = preg_split('/\R/u', trim($description ?? '')) ?: [];
        $hasMarkers = preg_match('/^\s*(?:\d+[.)]|[-*•])\s+/mu', $description ?? '') === 1;
        $points = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $isMarked = preg_match('/^(?:\d+[.)]|[-*•])\s+/u', $line) === 1;
            $text = preg_replace('/^(?:\d+[.)]|[-*•])\s+/u', '', $line);

            if ($hasMarkers && ! $isMarked && $points !== []) {
                $points[array_key_last($points)]['text'] .= "\n".$text;
            } else {
                $points[] = ['text' => $text];
            }
        }

        return $points;
    }

    /** @param array<array-key, array{text?: string|null}> $points */
    public static function toDescription(array $points): ?string
    {
        $lines = [];

        foreach ($points as $point) {
            $text = trim($point['text'] ?? '');

            if ($text !== '') {
                $lines[] = (count($lines) + 1).'. '.$text;
            }
        }

        return $lines === [] ? null : implode("\n", $lines);
    }
}
