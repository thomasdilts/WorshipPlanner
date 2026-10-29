<?php

namespace App\Support;

use App\Models\Language;
use Throwable;

final class AvailableLocales
{
    /**
     * Locales the UI can render (JSON lang files). English first = guest fallback.
     *
     * @return list<string>
     */
    public static function codes(): array
    {
        $fromFiles = self::fromLangFiles();

        try {
            $fromDb = Language::query()
                ->active()
                ->ordered()
                ->pluck('iso')
                ->map(fn (mixed $iso): string => strtolower((string) $iso))
                ->all();

            if ($fromDb !== []) {
                $intersection = array_values(array_intersect($fromDb, $fromFiles));

                if ($intersection !== []) {
                    return self::englishFirst($intersection);
                }
            }
        } catch (Throwable) {
            // DB unavailable (early boot / missing migrations).
        }

        return self::englishFirst($fromFiles);
    }

    /**
     * @return list<string>
     */
    protected static function fromLangFiles(): array
    {
        $codes = [];

        foreach (glob(lang_path('*.json')) ?: [] as $path) {
            $codes[] = strtolower(pathinfo($path, PATHINFO_FILENAME));
        }

        return $codes !== [] ? $codes : ['en'];
    }

    /**
     * @param  list<string>  $codes
     * @return list<string>
     */
    protected static function englishFirst(array $codes): array
    {
        $codes = array_values(array_unique($codes));

        usort($codes, function (string $a, string $b): int {
            if ($a === 'en') {
                return -1;
            }

            if ($b === 'en') {
                return 1;
            }

            return $a <=> $b;
        });

        return $codes;
    }
}
