#!/usr/bin/env php
<?php

/**
 * Wrap common Filament UI string literals with __().
 * Skips already-wrapped calls and dynamic concatenations.
 */
$root = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../app/Filament'));

$methods = [
    'label', 'title', 'helperText', 'description', 'placeholder', 'tooltip',
    'modalHeading', 'modalSubmitActionLabel', 'modalCancelActionLabel',
    'addActionLabel', 'emptyStateHeading', 'emptyStateDescription',
    'successNotificationTitle', 'suffix', 'prefix', 'heading',
];

$methodAlt = implode('|', $methods);

// IMPORTANT: use single-quoted patterns — \1/\2 are octal escapes in double quotes.
$chainPattern = '/->('.$methodAlt.')\(\s*([\'"])((?:\\\\.|(?!\2).)*)\2(\s*(?:,|\)))/';
$sectionPattern = '/Section::make\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1\s*\)/';

$changedFiles = 0;
$stringKeys = [];

foreach ($root as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $original = file_get_contents($path);
    $content = $original;

    $content = preg_replace_callback(
        $chainPattern,
        function (array $m) use (&$stringKeys): string {
            $method = $m[1];
            $quote = $m[2];
            $text = $m[3];
            $tail = $m[4];
            $decoded = stripcslashes($text);

            if ($decoded === '' || $decoded === '—' || str_contains($text, '__(') || str_contains($text, '.$')) {
                return $m[0];
            }

            $stringKeys[$decoded] = true;

            return '->'.$method.'(__('.$quote.$text.$quote.')'.$tail;
        },
        $content
    );

    $content = preg_replace_callback(
        $sectionPattern,
        function (array $m) use (&$stringKeys): string {
            $quote = $m[1];
            $text = $m[2];
            $decoded = stripcslashes($text);
            $stringKeys[$decoded] = true;

            return 'Section::make(__('.$quote.$text.$quote.'))';
        },
        $content
    );

    if ($content !== $original) {
        file_put_contents($path, $content);
        $changedFiles++;
        echo 'Updated '.$path.PHP_EOL;
    }
}

$keys = array_keys($stringKeys);
sort($keys, SORT_STRING | SORT_FLAG_CASE);
@mkdir(dirname(__DIR__.'/../storage/app/i18n-keys.json'), 0777, true);
file_put_contents(__DIR__.'/../storage/app/i18n-keys.json', json_encode($keys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Changed {$changedFiles} files, ".count($keys).' keys'.PHP_EOL;
