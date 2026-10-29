#!/usr/bin/env php
<?php

/**
 * Extract all __('...') / __("...") keys from app code and merge into lang JSON files.
 * Missing sv/ar entries fall back to English (flagged for review).
 */
$roots = ['app', 'modules', 'resources/views'];
$keys = [];

$pattern = '/__\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/';

foreach ($roots as $root) {
    if (! is_dir($root)) {
        continue;
    }

    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

    foreach ($it as $file) {
        if (! $file->isFile()) {
            continue;
        }

        $name = $file->getFilename();
        if (! str_ends_with($name, '.php') && ! str_ends_with($name, '.blade.php')) {
            continue;
        }

        $content = file_get_contents($file->getPathname());

        if (preg_match_all($pattern, $content, $m)) {
            foreach ($m[2] as $raw) {
                $key = stripcslashes($raw);
                if ($key === '' || str_starts_with($key, 'app.') || str_contains($key, '::')) {
                    continue;
                }
                $keys[$key] = true;
            }
        }
    }
}

// Module definitions (translated at display time)
$moduleExtras = [
    'Bible verses',
    'Attach Bible passages to service-order tasks via the Zamar Bible API',
    'File sharing',
    'Share files with teams and attach files to service-order tasks',
    'Unavailability',
    'People and teams record unavailable dates; planners see conflicts when assigning tasks',
    'Song statistics',
    'See which catalog and manually entered songs appear on service-order tasks',
    'Song list',
    'Church song catalog with CSV import/export; pick songs on service-order tasks',
    'Pictures',
    'Shared picture library for attaching images to service-order tasks',
    'Teams',
    'Group people into teams, assign teams to service tasks, and message teammates',
    'Messaging',
    'Notify members by email (SMS & WhatsApp channels later)',
    'Events',
    'Church services and service-order planning',
    'Always enabled',
    'Enabled for this church',
    'Leave blank to keep the current password.',
    'Connected — :count bible(s)',
    'Created :created, updated :updated, skipped :skipped.',
    'From :date',
    'Copy “:name”',
    ':count events copied',
    ':name is unavailable',
    'Generated :when',
    'Template:',
    'Name',
    'Updated',
];

foreach ($moduleExtras as $extra) {
    $keys[$extra] = true;
}

$allKeys = array_keys($keys);
sort($allKeys, SORT_STRING | SORT_FLAG_CASE);

$svKnown = json_decode(file_get_contents('lang/sv.json'), true) ?: [];
$arKnown = json_decode(file_get_contents('lang/ar.json'), true) ?: [];

// Lightweight Swedish/Arabic for common new keys (others stay English until filled)
$svExtra = [
    'Always enabled' => 'Alltid aktiverad',
    'Enabled for this church' => 'Aktiverad för denna församling',
    'Leave blank to keep the current password.' => 'Lämna tomt för att behålla nuvarande lösenord.',
    'Connected — :count bible(s)' => 'Ansluten — :count bibel/biblar',
    'Created :created, updated :updated, skipped :skipped.' => 'Skapade :created, uppdaterade :updated, hoppade över :skipped.',
    'From :date' => 'Från :date',
    'Copy “:name”' => 'Kopiera ”:name”',
    ':count events copied' => ':count evenemang kopierade',
    ':name is unavailable' => ':name är otillgänglig',
    'Generated :when' => 'Genererad :when',
    'Template:' => 'Mall:',
    'Name' => 'Namn',
    'Updated' => 'Uppdaterad',
    'Attach Bible passages to service-order tasks via the Zamar Bible API' => 'Bifoga bibelställen till gudstjänstuppgifter via Zamar Bible API',
    'Share files with teams and attach files to service-order tasks' => 'Dela filer med team och bifoga filer till gudstjänstuppgifter',
    'People and teams record unavailable dates; planners see conflicts when assigning tasks' => 'Personer och team registrerar otillgängliga datum; planerare ser konflikter vid tilldelning',
    'See which catalog and manually entered songs appear on service-order tasks' => 'Se vilka katalog- och manuellt inlagda sånger som används i gudstjänstuppgifter',
    'Church song catalog with CSV import/export; pick songs on service-order tasks' => 'Församlingens sångkatalog med CSV-import/export; välj sånger på gudstjänstuppgifter',
    'Shared picture library for attaching images to service-order tasks' => 'Delat bildbibliotek för att bifoga bilder till gudstjänstuppgifter',
    'Group people into teams, assign teams to service tasks, and message teammates' => 'Gruppera personer i team, tilldela team till uppgifter och meddela teammedlemmar',
    'Notify members by email (SMS & WhatsApp channels later)' => 'Meddela medlemmar via e-post (SMS och WhatsApp senare)',
    'Church services and service-order planning' => 'Gudstjänster och planering av gudstjänstordning',
    'Desc: ' => 'Beskr: ',
    'Needs: ' => 'Behov: ',
    'Song: ' => 'Sång: ',
    'Person: ' => 'Person: ',
    'Team: ' => 'Team: ',
    'Files: ' => 'Filer: ',
];

$arExtra = [
    'Always enabled' => 'مفعّل دائمًا',
    'Enabled for this church' => 'مفعّل لهذه الكنيسة',
    'Leave blank to keep the current password.' => 'اتركه فارغًا للاحتفاظ بكلمة المرور الحالية.',
    'Connected — :count bible(s)' => 'متصل — :count ترجمة/ترجمات',
    'Created :created, updated :updated, skipped :skipped.' => 'أُنشئ :created، حُدّث :updated، تُخطي :skipped.',
    'From :date' => 'من :date',
    'Copy “:name”' => 'نسخ ”:name”',
    ':count events copied' => 'تم نسخ :count فعاليات',
    ':name is unavailable' => ':name غير متاح',
    'Generated :when' => 'أُنشئ :when',
    'Template:' => 'القالب:',
    'Name' => 'الاسم',
    'Updated' => 'محدّث',
    'Attach Bible passages to service-order tasks via the Zamar Bible API' => 'إرفاق مقاطع كتابية بمهام ترتيب الخدمة عبر واجهة Zamar Bible',
    'Share files with teams and attach files to service-order tasks' => 'مشاركة الملفات مع الفرق وإرفاقها بمهام ترتيب الخدمة',
    'People and teams record unavailable dates; planners see conflicts when assigning tasks' => 'يسجّل الأشخاص والفرق تواريخ عدم التوفر؛ يرى المخططون التعارضات عند التعيين',
    'See which catalog and manually entered songs appear on service-order tasks' => 'عرض الأغاني من الكتالوج والمدخلة يدويًا المستخدمة في مهام ترتيب الخدمة',
    'Church song catalog with CSV import/export; pick songs on service-order tasks' => 'كتالوج أغاني الكنيسة مع استيراد/تصدير CSV؛ اختيار الأغاني في مهام ترتيب الخدمة',
    'Shared picture library for attaching images to service-order tasks' => 'مكتبة صور مشتركة لإرفاق الصور بمهام ترتيب الخدمة',
    'Group people into teams, assign teams to service tasks, and message teammates' => 'تجميع الأشخاص في فرق، وتعيين الفرق للمهام، ومراسلة أعضاء الفريق',
    'Notify members by email (SMS & WhatsApp channels later)' => 'إشعار الأعضاء عبر البريد (قناتا SMS وWhatsApp لاحقًا)',
    'Church services and service-order planning' => 'خدمات الكنيسة وتخطيط ترتيب الخدمة',
    'Desc: ' => 'الوصف: ',
    'Needs: ' => 'الاحتياجات: ',
    'Song: ' => 'الأغنية: ',
    'Person: ' => 'الشخص: ',
    'Team: ' => 'الفريق: ',
    'Files: ' => 'الملفات: ',
];

$en = [];
$sv = [];
$ar = [];
$missingSv = [];
$missingAr = [];

foreach ($allKeys as $key) {
    $en[$key] = $key;
    $sv[$key] = $svKnown[$key] ?? $svExtra[$key] ?? $key;
    $ar[$key] = $arKnown[$key] ?? $arExtra[$key] ?? $key;

    if (! isset($svKnown[$key]) && ! isset($svExtra[$key])) {
        $missingSv[] = $key;
    }
    if (! isset($arKnown[$key]) && ! isset($arExtra[$key])) {
        $missingAr[] = $key;
    }
}

$flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
file_put_contents('lang/en.json', json_encode($en, $flags)."\n");
file_put_contents('lang/sv.json', json_encode($sv, $flags)."\n");
file_put_contents('lang/ar.json', json_encode($ar, $flags)."\n");

echo 'Keys: '.count($allKeys)."\n";
echo 'Missing SV (left as English): '.count($missingSv)."\n";
echo 'Missing AR (left as English): '.count($missingAr)."\n";

if ($missingSv !== []) {
    file_put_contents('storage/app/i18n-missing-sv.txt', implode("\n", $missingSv)."\n");
}
if ($missingAr !== []) {
    file_put_contents('storage/app/i18n-missing-ar.txt', implode("\n", $missingAr)."\n");
}
