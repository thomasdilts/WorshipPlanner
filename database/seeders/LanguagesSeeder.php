<?php

namespace Database\Seeders;

use App\Enums\TextDirection;
use App\Models\Language;
use App\Models\User;
use Illuminate\Database\Seeder;

class LanguagesSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            [
                'iso' => 'en',
                'name_en' => 'English',
                'name_native' => 'English',
                'font' => 'Inter, ui-sans-serif, system-ui, sans-serif',
                'direction' => TextDirection::Ltr,
                'sort_order' => 10,
            ],
            [
                'iso' => 'sv',
                'name_en' => 'Swedish',
                'name_native' => 'Svenska',
                'font' => 'Inter, ui-sans-serif, system-ui, sans-serif',
                'direction' => TextDirection::Ltr,
                'sort_order' => 20,
            ],
            [
                'iso' => 'ar',
                'name_en' => 'Arabic',
                'name_native' => 'العربية',
                'font' => '"Noto Naskh Arabic", "Amiri", "Scheherazade New", serif',
                'direction' => TextDirection::Rtl,
                'sort_order' => 30,
            ],
        ];

        foreach ($languages as $language) {
            Language::query()->updateOrCreate(
                ['iso' => $language['iso']],
                [
                    ...$language,
                    'is_active' => true,
                ],
            );
        }

        $englishId = Language::query()->where('iso', 'en')->value('id');

        if ($englishId !== null) {
            User::query()
                ->whereNull('language_id')
                ->update(['language_id' => $englishId]);
        }
    }
}
