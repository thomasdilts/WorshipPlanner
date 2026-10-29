<?php

namespace Database\Factories;

use App\Enums\TextDirection;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    protected $model = Language::class;

    public function definition(): array
    {
        $name = fake()->unique()->languageCode();

        return [
            'iso' => $name,
            'name_en' => fake()->words(2, true),
            'name_native' => fake()->words(2, true),
            'font' => 'Inter, ui-sans-serif, system-ui, sans-serif',
            'direction' => TextDirection::Ltr,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
