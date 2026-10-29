#!/usr/bin/env php
<?php

$dirs = ['app/Filament', 'app/Enums', 'app/Core', 'app/Support', 'app/Services', 'app/Http', 'resources/views', 'modules'];

$patterns = [
    'chain' => '/->(?:label|title|helperText|description|placeholder|tooltip|modalHeading|modalSubmitActionLabel|modalCancelActionLabel|addActionLabel|emptyStateHeading|emptyStateDescription|successNotificationTitle|body|heading|content)\(\s*[\'"][^\'"]+[\'"]/',
    'section' => '/(?:Section|Tabs|Tab)::make\(\s*[\'"][^\'"]+[\'"]/',
    'return' => '/return\s+[\'"][A-Za-z][^\'"]{2,}[\'"]\s*;/',
];

foreach ($dirs as $dir) {
    if (! is_dir($dir)) {
        continue;
    }

    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

    foreach ($it as $f) {
        if (! $f->isFile()) {
            continue;
        }

        $name = $f->getFilename();
        if (! str_ends_with($name, '.php')) {
            continue;
        }

        $path = $f->getPathname();
        $c = file_get_contents($path);
        $lines = explode("\n", $c);

        foreach ($lines as $i => $line) {
            if (str_contains($line, '__(') || str_contains($line, 'trans(')) {
                continue;
            }

            foreach ($patterns as $p) {
                if (preg_match($p, $line)) {
                    echo $path.':'.($i + 1).': '.trim($line)."\n";
                    break;
                }
            }
        }
    }
}
