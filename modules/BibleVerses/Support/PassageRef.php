<?php

namespace Modules\BibleVerses\Support;

use InvalidArgumentException;

final class PassageRef
{
    /**
     * Build an OSIS passage ref for the Zamar API (same book only).
     */
    public static function build(
        string $book,
        int $chapter,
        int $verseStart,
        ?int $verseEnd = null,
    ): string {
        $book = strtoupper(trim($book));

        if ($book === '' || $chapter < 1 || $verseStart < 1) {
            throw new InvalidArgumentException('Invalid bible passage.');
        }

        if ($verseEnd === null || $verseEnd <= $verseStart) {
            return "{$book}.{$chapter}.{$verseStart}";
        }

        return "{$book}.{$chapter}.{$verseStart}-{$verseEnd}";
    }

    /**
     * @return array{book: string, chapter: int, verse_start: int, verse_end: int|null}|null
     */
    public static function parse(string $ref): ?array
    {
        $ref = strtoupper(trim($ref));

        if (preg_match('/^([A-Z0-9]+)\.(\d+)\.(\d+)-(\d+)$/', $ref, $m)) {
            return [
                'book' => $m[1],
                'chapter' => (int) $m[2],
                'verse_start' => (int) $m[3],
                'verse_end' => (int) $m[4],
            ];
        }

        if (preg_match('/^([A-Z0-9]+)\.(\d+)\.(\d+)$/', $ref, $m)) {
            return [
                'book' => $m[1],
                'chapter' => (int) $m[2],
                'verse_start' => (int) $m[3],
                'verse_end' => null,
            ];
        }

        // Cross-chapter compact: GEN.1.30-2.3
        if (preg_match('/^([A-Z0-9]+)\.(\d+)\.(\d+)-(\d+)\.(\d+)$/', $ref, $m)) {
            return [
                'book' => $m[1],
                'chapter' => (int) $m[2],
                'verse_start' => (int) $m[3],
                'verse_end' => (int) $m[5],
            ];
        }

        return null;
    }

    public static function label(string $bookOsis, int $chapter, int $verseStart, ?int $verseEnd = null): string
    {
        $book = OsisCanon::name($bookOsis) ?? $bookOsis;
        $range = $verseEnd !== null && $verseEnd > $verseStart
            ? "{$verseStart}–{$verseEnd}"
            : (string) $verseStart;

        return "{$book} {$chapter}:{$range}";
    }
}
