<?php

namespace Modules\BibleVerses\Support;

/**
 * Protestant canon metadata for the passage picker (OSIS codes match Zamar).
 * Chapter counts are local so the UI works without an API round-trip.
 */
final class OsisCanon
{
    /**
     * @var array<string, array{name: string, chapters: int}>
     */
    public const BOOKS = [
        'GEN' => ['name' => 'Genesis', 'chapters' => 50],
        'EXOD' => ['name' => 'Exodus', 'chapters' => 40],
        'LEV' => ['name' => 'Leviticus', 'chapters' => 27],
        'NUM' => ['name' => 'Numbers', 'chapters' => 36],
        'DEUT' => ['name' => 'Deuteronomy', 'chapters' => 34],
        'JOSH' => ['name' => 'Joshua', 'chapters' => 24],
        'JUDG' => ['name' => 'Judges', 'chapters' => 21],
        'RUTH' => ['name' => 'Ruth', 'chapters' => 4],
        '1SAM' => ['name' => '1 Samuel', 'chapters' => 31],
        '2SAM' => ['name' => '2 Samuel', 'chapters' => 24],
        '1KGS' => ['name' => '1 Kings', 'chapters' => 22],
        '2KGS' => ['name' => '2 Kings', 'chapters' => 25],
        '1CHR' => ['name' => '1 Chronicles', 'chapters' => 29],
        '2CHR' => ['name' => '2 Chronicles', 'chapters' => 36],
        'EZRA' => ['name' => 'Ezra', 'chapters' => 10],
        'NEH' => ['name' => 'Nehemiah', 'chapters' => 13],
        'ESTH' => ['name' => 'Esther', 'chapters' => 10],
        'JOB' => ['name' => 'Job', 'chapters' => 42],
        'PS' => ['name' => 'Psalms', 'chapters' => 150],
        'PROV' => ['name' => 'Proverbs', 'chapters' => 31],
        'ECCL' => ['name' => 'Ecclesiastes', 'chapters' => 12],
        'SONG' => ['name' => 'Song of Solomon', 'chapters' => 8],
        'ISA' => ['name' => 'Isaiah', 'chapters' => 66],
        'JER' => ['name' => 'Jeremiah', 'chapters' => 52],
        'LAM' => ['name' => 'Lamentations', 'chapters' => 5],
        'EZEK' => ['name' => 'Ezekiel', 'chapters' => 48],
        'DAN' => ['name' => 'Daniel', 'chapters' => 12],
        'HOS' => ['name' => 'Hosea', 'chapters' => 14],
        'JOEL' => ['name' => 'Joel', 'chapters' => 3],
        'AMOS' => ['name' => 'Amos', 'chapters' => 9],
        'OBAD' => ['name' => 'Obadiah', 'chapters' => 1],
        'JONAH' => ['name' => 'Jonah', 'chapters' => 4],
        'MIC' => ['name' => 'Micah', 'chapters' => 7],
        'NAH' => ['name' => 'Nahum', 'chapters' => 3],
        'HAB' => ['name' => 'Habakkuk', 'chapters' => 3],
        'ZEPH' => ['name' => 'Zephaniah', 'chapters' => 3],
        'HAG' => ['name' => 'Haggai', 'chapters' => 2],
        'ZECH' => ['name' => 'Zechariah', 'chapters' => 14],
        'MAL' => ['name' => 'Malachi', 'chapters' => 4],
        'MATT' => ['name' => 'Matthew', 'chapters' => 28],
        'MARK' => ['name' => 'Mark', 'chapters' => 16],
        'LUKE' => ['name' => 'Luke', 'chapters' => 24],
        'JOHN' => ['name' => 'John', 'chapters' => 21],
        'ACTS' => ['name' => 'Acts', 'chapters' => 28],
        'ROM' => ['name' => 'Romans', 'chapters' => 16],
        '1COR' => ['name' => '1 Corinthians', 'chapters' => 16],
        '2COR' => ['name' => '2 Corinthians', 'chapters' => 13],
        'GAL' => ['name' => 'Galatians', 'chapters' => 6],
        'EPH' => ['name' => 'Ephesians', 'chapters' => 6],
        'PHIL' => ['name' => 'Philippians', 'chapters' => 4],
        'COL' => ['name' => 'Colossians', 'chapters' => 4],
        '1THESS' => ['name' => '1 Thessalonians', 'chapters' => 5],
        '2THESS' => ['name' => '2 Thessalonians', 'chapters' => 3],
        '1TIM' => ['name' => '1 Timothy', 'chapters' => 6],
        '2TIM' => ['name' => '2 Timothy', 'chapters' => 4],
        'TITUS' => ['name' => 'Titus', 'chapters' => 3],
        'PHLM' => ['name' => 'Philemon', 'chapters' => 1],
        'HEB' => ['name' => 'Hebrews', 'chapters' => 13],
        'JAS' => ['name' => 'James', 'chapters' => 5],
        '1PET' => ['name' => '1 Peter', 'chapters' => 5],
        '2PET' => ['name' => '2 Peter', 'chapters' => 3],
        '1JOHN' => ['name' => '1 John', 'chapters' => 5],
        '2JOHN' => ['name' => '2 John', 'chapters' => 1],
        '3JOHN' => ['name' => '3 John', 'chapters' => 1],
        'JUDE' => ['name' => 'Jude', 'chapters' => 1],
        'REV' => ['name' => 'Revelation', 'chapters' => 22],
    ];

    /**
     * @return array<string, string> osis => name
     */
    public static function bookOptions(): array
    {
        $options = [];

        foreach (self::BOOKS as $osis => $meta) {
            $options[$osis] = $meta['name'];
        }

        return $options;
    }

    public static function name(string $osis): ?string
    {
        return self::BOOKS[strtoupper($osis)]['name'] ?? null;
    }

    public static function chapterCount(string $osis): int
    {
        return self::BOOKS[strtoupper($osis)]['chapters'] ?? 1;
    }

    /**
     * @return array<int, string>
     */
    public static function chapterOptions(string $osis): array
    {
        $count = self::chapterCount($osis);
        $options = [];

        for ($i = 1; $i <= $count; $i++) {
            $options[$i] = (string) $i;
        }

        return $options;
    }

    /**
     * Generous verse list for the picker (Psalms max 176). Invalid ranges are
     * rejected when the Zamar API is asked for the passage.
     *
     * @return array<int, string>
     */
    public static function verseOptions(string $osis = 'PS'): array
    {
        $max = strtoupper($osis) === 'PS' ? 176 : 80;
        $options = [];

        for ($i = 1; $i <= $max; $i++) {
            $options[$i] = (string) $i;
        }

        return $options;
    }
}
