#!/usr/bin/env php
<?php

/**
 * Convert static Filament navigation/model label properties into __()-aware getters.
 */
$root = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../app/Filament'));

$changedFiles = 0;
$stringKeys = [];

$replacements = [
    // ?string props
    [
        'pattern' => '/protected\s+static\s+\?string\s+\$(navigationLabel|modelLabel|pluralModelLabel|title|heading)\s*=\s*([\'"])((?:\\\\.|(?!\2).)*)\2\s*;/',
        'callback' => function (array $m) use (&$stringKeys): string {
            $prop = $m[1];
            $quote = $m[2];
            $text = $m[3];
            $stringKeys[stripcslashes($text)] = true;
            $method = 'get'.ucfirst($prop);

            return "public static function {$method}(): ?string\n    {\n        return __({$quote}{$text}{$quote});\n    }";
        },
    ],
    // plain string props
    [
        'pattern' => '/protected\s+static\s+string\s+\$(navigationLabel|navigationGroup|modelLabel|pluralModelLabel)\s*=\s*([\'"])((?:\\\\.|(?!\2).)*)\2\s*;/',
        'callback' => function (array $m) use (&$stringKeys): string {
            $prop = $m[1];
            $quote = $m[2];
            $text = $m[3];
            $stringKeys[stripcslashes($text)] = true;
            $method = 'get'.ucfirst($prop);

            return "public static function {$method}(): string\n    {\n        return __({$quote}{$text}{$quote});\n    }";
        },
    ],
    // string|UnitEnum|null $navigationGroup
    [
        'pattern' => '/protected\s+static\s+string\|UnitEnum\|null\s+\$navigationGroup\s*=\s*([\'"])((?:\\\\.|(?!\1).)*)\1\s*;/',
        'callback' => function (array $m) use (&$stringKeys): string {
            $quote = $m[1];
            $text = $m[2];
            $stringKeys[stripcslashes($text)] = true;

            return "public static function getNavigationGroup(): string|UnitEnum|null\n    {\n        return __({$quote}{$text}{$quote});\n    }";
        },
    ],
    // string|\UnitEnum|null alternate
    [
        'pattern' => '/protected\s+static\s+string\|\\\\UnitEnum\|null\s+\$navigationGroup\s*=\s*([\'"])((?:\\\\.|(?!\1).)*)\1\s*;/',
        'callback' => function (array $m) use (&$stringKeys): string {
            $quote = $m[1];
            $text = $m[2];
            $stringKeys[stripcslashes($text)] = true;

            return "public static function getNavigationGroup(): string|\\UnitEnum|null\n    {\n        return __({$quote}{$text}{$quote});\n    }";
        },
    ],
];

foreach ($root as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $original = file_get_contents($path);
    $content = $original;

    foreach ($replacements as $rule) {
        $content = preg_replace_callback($rule['pattern'], $rule['callback'], $content);
    }

    if ($content !== $original) {
        file_put_contents($path, $content);
        $changedFiles++;
        echo 'Updated '.$path.PHP_EOL;
    }
}

$keysPath = __DIR__.'/../storage/app/i18n-keys.json';
$existing = is_file($keysPath) ? (json_decode(file_get_contents($keysPath), true) ?: []) : [];
$merged = array_values(array_unique(array_merge($existing, array_keys($stringKeys))));
sort($merged, SORT_STRING | SORT_FLAG_CASE);
file_put_contents($keysPath, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Changed {$changedFiles} files, added ".count($stringKeys).' nav keys'.PHP_EOL;
